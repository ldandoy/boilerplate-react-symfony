#!/usr/bin/env bash
# Garde-fous d'architecture appliqués à Claude Code (cf. « Règles d'or » dans AGENTS.md).
# Appelé par les hooks de .claude/settings.json sur Write / Edit :
#   pre  : avant l'écriture, refuse selon le chemin du fichier
#   post : après l'écriture, vérifie le contenu du fichier
# Code de sortie 2 = refus : le message sur stderr est renvoyé à Claude, qui doit se corriger.
# Écrit en bash 3.2 sans jq ni python pour tourner partout (macOS, Linux, Git Bash sous Windows).

mode="$1"
input=$(cat)

# tool_input.file_path, chemins Windows normalisés en « / »
file=$(printf '%s' "$input" | sed -n 's/.*"file_path"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -n 1)
file=$(printf '%s' "$file" | sed 's#\\\\#/#g')
[ -z "$file" ] && exit 0

block() {
  printf 'Refusé par .claude/hooks/guard.sh : %s\n' "$1" >&2
  exit 2
}

remind() {
  printf '{"hookSpecificOutput":{"hookEventName":"PostToolUse","additionalContext":"%s"}}\n' "$1"
  exit 0
}

if [ "$mode" = "pre" ]; then
  case "$file" in
    */backend/migrations/*)
      block "une migration ne s'écrit pas à la main. Modifier l'entité puis lancer « docker exec boilterplate_back php bin/console make:migration »." ;;
    */backend/src/Controller/Admin/*) ;;
    */backend/src/Controller/HomeController.php | */backend/src/Controller/SecurityController.php) ;;
    */backend/src/Controller/*)
      block "règles d'or 1 et 2 : pas de contrôleur Symfony hors back-office. Une route d'API passe par API Platform (#[ApiResource] sur l'entité, ou DTO dans src/ApiResource + Provider/Processor dans src/State). Une page d'admin passe par un CrudController EasyAdmin dans src/Controller/Admin." ;;
    */backend/templates/*.twig)
      if [ ! -e "$file" ] && ! printf '%s' "$file" | grep -qi 'email'; then
        block "règle d'or 2 : pas de template Twig écrit à la main (le back-office est généré par EasyAdmin, le front est en React). Seuls les templates d'emails (nom contenant « email ») peuvent être créés."
      fi ;;
  esac
  exit 0
fi

[ -f "$file" ] || exit 0

case "$file" in
  */backend/src/*.php)
    if grep -qE 'JsonResponse|->json\(' "$file"; then
      block "règle d'or 1 : pas de JsonResponse ni de \$this->json(). Exposer la donnée via API Platform (DTO dans src/ApiResource + Provider/Processor dans src/State, cf. Me.php / MeProvider.php)."
    fi
    if grep -qE "#\[Route\(.*['\"]/api" "$file"; then
      block "règle d'or 1 : aucune route /api ne se déclare avec #[Route]. Utiliser une opération API Platform."
    fi
    if grep -q '#\[ApiResource' "$file" && ! grep -q 'operations:' "$file"; then
      block "règle d'or 1 : #[ApiResource] doit lister explicitement ses operations (GetCollection, Get, Post, Patch, Delete…), avec security sur les opérations sensibles."
    fi
    case "$file" in
      */backend/src/Controller/Admin/*)
        if ! grep -qE 'extends (AbstractCrudController|AbstractDashboardController)' "$file"; then
          block "règle d'or 2 : un contrôleur d'admin étend AbstractCrudController (cf. UserCrudController) et s'ajoute au menu dans DashboardController::configureMenuItems()."
        fi ;;
    esac ;;
  */frontend/src/*.test.ts | */frontend/src/*.test.tsx) ;;
  */frontend/src/*.ts | */frontend/src/*.tsx)
    if grep -qE '(^|[^.[:alnum:]_])fetch\(' "$file"; then
      block "pas de fetch() dans le front. Appels authentifiés : instance api de src/lib/axios.ts. Appels publics : axios brut avec VITE_API_URL."
    fi
    case "$file" in
      */frontend/src/lib/axios.ts) ;;
      *)
        if grep -qE "from ['\"]axios['\"]" "$file" && ! grep -qE "lib/axios['\"]" "$file"; then
          remind "Rappel : axios brut n'est réservé qu'aux appels publics (login, register, mot de passe oublié). Pour tout appel authentifié, utiliser l'instance api de src/lib/axios.ts."
        fi ;;
    esac ;;
esac
exit 0
