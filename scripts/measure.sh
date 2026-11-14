#!/usr/bin/env bash
# 첫 화면을 N번(기본 10) 요청해 DB 쿼리 수와 응답 시간을 출력합니다.
# 첫 1회는 캐시 준비용으로 버리고 중앙값을 냅니다.
set -euo pipefail
URL="${URL:-http://127.0.0.1:${PORT:-9400}/}"
N="${N:-10}"
curl -s -o /dev/null "$URL"
times=()
for _ in $(seq 1 "$N"); do
	out="$(curl -s -w '\n__TOTAL__=%{time_total}' "$URL")"
	q="$(printf '%s' "$out" | sed -n 's/.*query-meter: queries=\([0-9]*\) time_ms=\([0-9]*\).*/\1/p')"
	ms="$(printf '%s' "$out" | sed -n 's/.*query-meter: queries=[0-9]* time_ms=\([0-9]*\).*/\1/p')"
	total="$(printf '%s' "$out" | sed -n 's/^__TOTAL__=//p')"
	echo "queries=$q php_ms=$ms curl_total_s=$total"
	times+=("$total")
done
median="$(printf '%s\n' "${times[@]}" | sort -n | awk '{a[NR]=$1} END{print (NR%2)?a[(NR+1)/2]:(a[NR/2]+a[NR/2+1])/2}')"
echo "median_curl_total_s=$median (n=$N)"
