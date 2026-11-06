#!/usr/bin/env bash
# Tests de bout en bout (curl) des scénarios serveur du sujet.
# Prérequis : application lancée + base initialisée avec db/seed.php.
# Usage : tests/smoke.sh [URL_DE_BASE]    (défaut : http://127.0.0.1:8080)
BASE="${1:-http://127.0.0.1:8080}"
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
PASS=0; FAIL=0

check() {  # check "libellé" "attendu" "obtenu"
    if [ "$2" = "$3" ]; then PASS=$((PASS+1)); printf '| %s | %s | %s | OK |\n' "$1" "$2" "$3"
    else FAIL=$((FAIL+1)); printf '| %s | %s | %s | ÉCHEC |\n' "$1" "$2" "$3"; fi
}
code()  { curl -s -o /dev/null -w '%{http_code}' "$@"; }
token() { curl -s -b "$TMP/$1" -c "$TMP/$1" "$BASE/$2" | grep -o 'name="csrf_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }
login() {  # login jar email password -> code HTTP
    local t; t=$(token "$1" login.php)
    curl -s -o /dev/null -w '%{http_code}|%{redirect_url}' -b "$TMP/$1" -c "$TMP/$1" \
        --data-urlencode "csrf_token=$t" --data-urlencode "email=$2" --data-urlencode "password=$3" "$BASE/login.php"
}
api() { curl -s -b "$TMP/$1" "$BASE/api/tickets.php$2"; }
urlenc() { python3 -c "import urllib.parse,sys;print(urllib.parse.quote(sys.argv[1]))" "$1"; }
jq_() { python3 -c "import sys,json; d=json.load(sys.stdin); print($1)"; }
id_by_title() { api "$1" "?q=$(urlenc "$2")" | jq_ "d['tickets'][0]['id']"; }
post() {  # post jar url data... -> code HTTP (avec jeton CSRF valide)
    local jar=$1 url=$2; shift 2; local t; t=$(token "$jar" "${PAGE:-dashboard.php}")
    curl -s -o /dev/null -w '%{http_code}' -b "$TMP/$jar" -c "$TMP/$jar" --data-urlencode "csrf_token=$t" "$@" "$BASE/$url"
}

echo '| Scénario | Attendu | Obtenu | Résultat |'
echo '|---|---|---|---|'

# 1. Connexion
r=$(login alice alice@example.test 'Alice-Demo-2026!'); check "1a Connexion utilisateur -> redirection" "302|$BASE/dashboard.php" "$r"
r=$(login admin admin@example.test 'Admin-Demo-2026!'); check "1b Connexion admin -> redirection" "302|$BASE/admin.php" "$r"
r=$(login bruno bruno@example.test 'Bruno-Demo-2026!'); check "1c Connexion 2e utilisateur" "302|$BASE/dashboard.php" "$r"
t=$(token bad login.php)
body=$(curl -s -b "$TMP/bad" -c "$TMP/bad" --data-urlencode "csrf_token=$t" -d 'email=alice@example.test&password=mauvais' "$BASE/login.php")
check "1d Mauvais mot de passe -> message d'échec" "oui" "$(echo "$body" | grep -q 'Identifiants incorrects' && echo oui || echo non)"
check "1e Page privée sans session -> redirection login" "302" "$(code "$BASE/dashboard.php")"

# 2. Création valide
before=$(api alice | jq_ "len(d['tickets'])")
PAGE=ticket_new.php
check "2 Création d'un ticket valide -> redirection" "302" "$(post alice ticket_new.php -d 'titre=Souris cassée en salle 3' -d 'categorie=materiel' --data-urlencode 'description=La souris du poste numéro 3 ne fonctionne plus du tout.')"
after=$(api alice | jq_ "len(d['tickets'])")
check "2b Le ticket apparaît dans la liste (+1)" "$((before+1))" "$after"
new=$(api alice "?q=Souris%20cass" | jq_ "d['tickets'][0]['id']")
check "2c Statut initial 'Ouvert'" "Ouvert" "$(api alice "?q=Souris%20cass" | jq_ "d['tickets'][0]['statut_label']")"
check "2d Priorité initiale 'Moyenne'" "Moyenne" "$(api alice "?q=Souris%20cass" | jq_ "d['tickets'][0]['priorite_label']")"

# 3. Formulaire incomplet / hors limites -> rien n'est créé
n0=$(api alice | jq_ "len(d['tickets'])")
post alice ticket_new.php -d 'titre=abc' -d 'categorie=' -d 'description=trop court' >/dev/null
long=$(python3 -c "print('x'*2001)")
post alice ticket_new.php -d 'titre=Titre valide ici' -d 'categorie=autre' --data-urlencode "description=$long" >/dev/null
post alice ticket_new.php -d 'titre=Titre valide ici' -d 'categorie=pirate' --data-urlencode 'description=Une description assez longue pour passer.' >/dev/null
check "3 Formulaires invalides refusés côté serveur (aucun ticket créé)" "$n0" "$(api alice | jq_ "len(d['tickets'])")"

# 4. Modification et suppression d'un ticket ouvert par son auteur
PAGE="ticket_edit.php?id=$new"
check "4a Modification par l'auteur (ticket ouvert)" "302" "$(post alice ticket_edit.php -d "id=$new" -d 'titre=Souris cassée MODIFIÉE' -d 'categorie=materiel' --data-urlencode 'description=Description modifiée pour le test de modification.')"
check "4b Titre mis à jour" "Souris cassée MODIFIÉE" "$(api alice "?q=MODIFI" | jq_ "d['tickets'][0]['titre']")"
PAGE=tickets.php
check "4c Suppression par l'auteur (POST + CSRF)" "302" "$(post alice ticket_delete.php -d "id=$new")"
check "4d Le ticket n'existe plus" "403" "$(code -b "$TMP/alice" "$BASE/ticket_view.php?id=$new")"

# 5. Accès au ticket d'un autre utilisateur
bid=$(id_by_title bruno "Accès refusé au dossier")
check "5a Alice lit le ticket de Bruno -> refus" "403" "$(code -b "$TMP/alice" "$BASE/ticket_view.php?id=$bid")"
check "5b Alice modifie le ticket de Bruno -> refus" "403" "$(PAGE=tickets.php post alice ticket_edit.php -d "id=$bid" -d 'titre=Piraté depuis Alice' -d 'categorie=autre' --data-urlencode 'description=Tentative de modification du ticket de quelqu un d autre.')"
check "5c Alice supprime le ticket de Bruno -> refus" "403" "$(PAGE=tickets.php post alice ticket_delete.php -d "id=$bid")"
check "5d Ticket de Bruno toujours intact" "Accès refusé au dossier partagé" "$(api bruno "?q=$(urlenc 'Accès refusé')" | jq_ "d['tickets'][0]['titre']")"
check "5e API : Alice ne reçoit que ses tickets (même avec user_id forcé)" "{'Alice Martin'}" "$(api alice '?user_id=3' | jq_ "set(t['auteur'] for t in d['tickets'])")"
check "5f API sans session -> 401" "401" "$(code "$BASE/api/tickets.php")"

# 6. Ticket en cours / résolu : ni modification ni suppression par l'auteur
enc=$(id_by_title alice "Wi-Fi instable"); res=$(id_by_title alice "Souris sans fil")
check "6a Modifier un ticket 'en cours' -> refus" "403" "$(PAGE=tickets.php post alice ticket_edit.php -d "id=$enc" -d 'titre=Titre changé en cours' -d 'categorie=reseau' --data-urlencode 'description=Tentative de modification d un ticket en cours.')"
check "6b Supprimer un ticket 'en cours' -> refus" "403" "$(PAGE=tickets.php post alice ticket_delete.php -d "id=$enc")"
check "6c Modifier un ticket 'résolu' -> refus" "403" "$(PAGE=tickets.php post alice ticket_edit.php -d "id=$res" -d 'titre=Titre changé résolu' -d 'categorie=materiel' --data-urlencode 'description=Tentative de modification d un ticket résolu.')"
check "6d Supprimer un ticket 'résolu' -> refus" "403" "$(PAGE=tickets.php post alice ticket_delete.php -d "id=$res")"

# 7. Administrateur : statut + réponse
aid=$(id_by_title admin "Projecteur de la salle")
PAGE="ticket_view.php?id=$aid"
check "7a Admin : ouvert -> en cours" "302" "$(post admin ticket_admin.php -d "id=$aid" -d action=statut)"
check "7b Statut devenu 'En cours'" "En cours" "$(api admin "?q=Projecteur" | jq_ "d['tickets'][0]['statut_label']")"
post admin ticket_admin.php -d "id=$aid" -d action=statut >/dev/null
check "7c Admin : en cours -> résolu" "Résolu" "$(api admin "?q=Projecteur" | jq_ "d['tickets'][0]['statut_label']")"
post admin ticket_admin.php -d "id=$aid" -d action=statut >/dev/null
check "7d Pas de réouverture : reste 'Résolu'" "Résolu" "$(api admin "?q=Projecteur" | jq_ "d['tickets'][0]['statut_label']")"
check "7e Admin : priorité -> Faible" "302" "$(post admin ticket_admin.php -d "id=$aid" -d action=priorite -d priorite=faible)"
check "7f Priorité devenue 'Faible'" "Faible" "$(api admin "?q=Projecteur" | jq_ "d['tickets'][0]['priorite_label']")"
check "7g Admin : réponse valide" "302" "$(post admin ticket_admin.php -d "id=$aid" -d action=reponse --data-urlencode 'contenu=Lampe remplacée, le projecteur fonctionne.')"
check "7h La réponse est visible par l'auteur" "oui" "$(curl -s -b "$TMP/alice" "$BASE/ticket_view.php?id=$aid" | grep -q 'Lampe remplacée' && echo oui || echo non)"
nrep() { curl -s -b "$TMP/alice" "$BASE/ticket_view.php?id=$aid" | grep -o 'Réponses ([0-9]*)' | grep -o '[0-9]*'; }
n_avant=$(nrep); post admin ticket_admin.php -d "id=$aid" -d action=reponse -d 'contenu=a' >/dev/null
check "7i Réponse d'1 caractère refusée (nombre de réponses inchangé)" "$n_avant" "$(nrep)"
check "7j Utilisateur ne peut pas agir en admin" "403" "$(PAGE=tickets.php post alice ticket_admin.php -d "id=$aid" -d action=priorite -d priorite=elevee)"

# 8. Recherche et filtres
check "8a Recherche par titre ('wifi' absent, 'Wi-Fi' présent)" "1" "$(api admin '?q=Wi-Fi' | jq_ "len(d['tickets'])")"
check "8b Recherche sans résultat" "0" "$(api admin '?q=zzzzintrouvable' | jq_ "len(d['tickets'])")"
check "8c Filtre statut=ouvert (admin)" "{'Ouvert'}" "$(api admin '?statut=ouvert' | jq_ "set(t['statut_label'] for t in d['tickets'])")"
check "8d Filtre priorité=elevee (admin)" "{'Élevée'}" "$(api admin '?priorite=elevee' | jq_ "set(t['priorite_label'] for t in d['tickets'])")"
check "8e Filtre catégorie=reseau (admin)" "{'Réseau'}" "$(api admin '?categorie=reseau' | jq_ "set(t['categorie_label'] for t in d['tickets'])")"
check "8f Valeur de filtre invalide ignorée (pas d'erreur)" "200" "$(code -b "$TMP/admin" "$BASE/api/tickets.php?statut=nimporte")"
check "8g Injection SQL dans la recherche -> 0 résultat, pas d'erreur" "0" "$(api admin "?q=%27%20OR%201%3D1%20--%20" | jq_ "len(d['tickets'])")"

# 10. CSRF
check "10a POST sans jeton CSRF -> refus" "403" "$(curl -s -o /dev/null -w '%{http_code}' -b "$TMP/admin" -d "id=$aid" -d action=statut "$BASE/ticket_admin.php")"
check "10b POST avec jeton invalide -> refus" "403" "$(curl -s -o /dev/null -w '%{http_code}' -b "$TMP/alice" -d 'csrf_token=0000' -d "id=$enc" "$BASE/ticket_delete.php")"
check "10c Suppression par simple GET -> refusée (405)" "405" "$(code -b "$TMP/alice" "$BASE/ticket_delete.php?id=$enc")"
check "10d Déconnexion par GET -> refusée (405)" "405" "$(code -b "$TMP/alice" "$BASE/logout.php")"

# 11. HTML affiché sans exécution
PAGE=ticket_new.php
post alice ticket_new.php -d 'categorie=autre' --data-urlencode 'titre=<script>alert(1)</script> test' --data-urlencode 'description=<img src=x onerror=alert(2)> description assez longue.' >/dev/null
xid=$(api alice "?q=test" | jq_ "[t['id'] for t in d['tickets'] if t['titre'].startswith('<script>')][0]")
page=$(curl -s -b "$TMP/alice" "$BASE/ticket_view.php?id=$xid")
check "11a Balises échappées dans la page (&lt;script&gt;)" "oui" "$(echo "$page" | grep -q '&lt;script&gt;alert(1)&lt;/script&gt;' && echo oui || echo non)"
check "11b Aucune balise <script>alert brute dans la page" "non" "$(echo "$page" | grep -q '<script>alert' && echo oui || echo non)"
check "11c Aucune balise <img onerror> brute" "non" "$(echo "$page" | grep -q '<img src=x' && echo oui || echo non)"
PAGE=tickets.php; post alice ticket_delete.php -d "id=$xid" >/dev/null

# Tableau de bord
check "12 Indicateurs admin cohérents avec la liste" "oui" "$(api admin | python3 -c "
import sys,json
d=json.load(sys.stdin)['tickets']; print('oui' if len(d)>=14 else 'non')")"

# Déconnexion
check "13 Déconnexion (POST) puis page privée -> redirection login" "302" "$(PAGE=dashboard.php post alice logout.php >/dev/null; code -b "$TMP/alice" "$BASE/dashboard.php")"

echo; echo "Réussis : $PASS  –  Échecs : $FAIL"
[ "$FAIL" -eq 0 ]
