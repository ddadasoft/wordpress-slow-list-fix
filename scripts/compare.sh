#!/usr/bin/env bash
# 수정 전(?ppl=before)과 수정 후(기본 주소)의 DB 쿼리 수와 목록이 같은지 확인합니다.
# 서버는 scripts/start.sh 로 띄운 뒤 실행합니다. 목록이 다르거나 수정 후 쿼리가 줄지 않으면 종료 코드 1입니다.
set -euo pipefail
BASE="${URL:-http://127.0.0.1:${PORT:-9400}/}"
N="${N:-5}"

one() {
	local url="$1"
	local html q
	html="$(curl -fsS "$url")"
	q="$(printf '%s' "$html" | sed -n 's/.*query-meter: queries=\([0-9]*\) time_ms=\([0-9]*\).*/\1 \2/p')"
	if [ -z "$q" ]; then
		echo "query-meter 없음: $url" >&2
		exit 1
	fi
	printf '%s\n' "$q"
}

lists_match() {
	python3 - "$BASE" <<'PY'
import sys, urllib.request, re
base = sys.argv[1]
def titles(url):
    html = urllib.request.urlopen(url, timeout=60).read().decode("utf-8", "replace")
    return re.findall(r'<li><a href="[^"]*">([^<]+)</a>', html)
b, a = titles(base + "?ppl=before"), titles(base)
print(f"titles_before={len(b)} titles_after={len(a)} same={b==a}")
sys.exit(0 if b and b == a else 1)
PY
}

echo "url=$BASE n=$N"
bqs=()
aqs=()
for _ in $(seq 1 "$N"); do
	read -r bq bms <<<"$(one "${BASE}?ppl=before")"
	read -r aq ams <<<"$(one "$BASE")"
	echo "before queries=$bq php_ms=$bms | after queries=$aq php_ms=$ams"
	bqs+=("$bq")
	aqs+=("$aq")
done
lists_match
python3 - "$N" "${bqs[@]}" "${aqs[@]}" <<'PY'
import statistics, sys
n = int(sys.argv[1])
nums = list(map(int, sys.argv[2:]))
b, a = nums[:n], nums[n:]
print(f"before_median_queries={statistics.median(b)} after_median_queries={statistics.median(a)}")
if not (statistics.median(a) < statistics.median(b)):
    sys.exit(1)
PY
