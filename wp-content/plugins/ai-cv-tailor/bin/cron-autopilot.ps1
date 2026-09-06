# ==============================================================================
# AI CV Tailor - Freelance Autopilot Cron Runner (PowerShell / Windows)
# ==============================================================================
# Usage:
#   .\cron-autopilot.ps1 [-WordPressPath "C:\path\to\wordpress"]
# ==============================================================================

param(
    [string]$WordPressPath = ""
)

if (-not $WordPressPath) {
    $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
    if (Test-Path "$scriptDir\..\..\..\..\wp-load.php") {
        $WordPressPath = (Resolve-Path "$scriptDir\..\..\..\..").Path
    } elseif (Test-Path ".\wp-load.php") {
        $WordPressPath = (Resolve-Path ".").Path
    }
}

if (-not $WordPressPath -or -not (Test-Path "$WordPressPath\wp-load.php")) {
    Write-Error "[ERROR] WordPress installation not found at: $WordPressPath"
    exit 1
}

$dateStr = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
Write-Host "================================================================================"
Write-Host "[$dateStr] [INFO] Starting AI CV Tailor Autopilot run..."
Write-Host "[$dateStr] [INFO] WordPress path: $WordPressPath"
Write-Host "================================================================================"

Set-Location $WordPressPath

# 1. Fetch
Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [STEP 1/3] Fetching new job opportunities..."
wp ai-cv-tailor autopilot fetch --path="$WordPressPath"

# 2. Analyze
Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [STEP 2/3] Analyzing opportunities with OpenAI..."
wp ai-cv-tailor autopilot analyze --path="$WordPressPath"

# 3. Generate
Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [STEP 3/3] Generating applications for matching opportunities..."
wp ai-cv-tailor autopilot generate-applications --path="$WordPressPath"

Write-Host "================================================================================"
Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] [SUCCESS] Autopilot run completed."
Write-Host "================================================================================"
