Set-Location (Get-Item $PSScriptRoot).Parent.FullName;

$version = Select-String -path .\analytics-wordpress.php "Version: (.*)" -AllMatches | Foreach-Object {$_.Matches} | Foreach-Object {$_.Groups[1].Value}

$zipFileName = "SegmentAnalyticsWordpress_${version}.zip"
if( Test-Path $zipFileName ) { Remove-Item $zipFileName }
Compress-Archive -DestinationPath $zipFileName -LiteralPath (
    Get-ChildItem . -Exclude bin, tests, .travis.yml, .gitignore, readme.md, phpunit.xml
)

Get-ChildItem $zipFileName;
Pop-Location;
