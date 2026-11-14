#!/usr/bin/env bash
# 로컬 WordPress(WordPress Playground, PHP 8.3 WASM + SQLite)를 띄웁니다.
# 필요: Node.js 20 이상. Docker와 PHP 설치는 필요 없습니다.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${PORT:-9400}"
exec npx -y @wp-playground/cli@3 server \
	--port "$PORT" \
	--php 8.3 \
	--wp latest \
	--mount "$ROOT/plugins/popular-posts-list:/wordpress/wp-content/plugins/popular-posts-list" \
	--mount "$ROOT/mu-plugins:/wordpress/wp-content/mu-plugins" \
	--mount "$ROOT/setup:/sample-setup" \
	--blueprint "$ROOT/setup/blueprint.json"
