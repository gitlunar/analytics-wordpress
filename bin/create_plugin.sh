BIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT_DIR="$( cd  "${BIN_DIR}/.." && pwd)"

version=$(cat "${ROOT_DIR}/analytics-wordpress.php" | sed -nE "s/^Version: (.*)$/\1/p") 

zip_file="${ROOT_DIR}/SegmentAnalyticsWordpress_${version}.zip"
rm -f "${zip_file}";
pushd "${ROOT_DIR}";
zip -r "${zip_file}" * -x bin/ bin/* tests/ tests/* .travis.yml .gitignore readme.md phpunit.xml;
ls "${zip_file}";
popd;