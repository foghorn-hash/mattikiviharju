#!/usr/bin/env bash
# ==============================================================================
# AI CV Tailor - Freelance Autopilot Cron Runner
# ==============================================================================
# Usage:
#   ./cron-autopilot.sh [/path/to/wordpress]
#
# Crontab example:
#   0 7 * * * /path/to/wordpress/wp-content/plugins/ai-cv-tailor/bin/cron-autopilot.sh /path/to/wordpress >> /path/to/wordpress/wp-content/uploads/autopilot-cron.log 2>&1
# ==============================================================================

# Determine script directory and default WordPress path
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP_PATH="${1:-}"

# If WP_PATH is not given as argument, try to auto-detect relative to script
if [ -z "$WP_PATH" ]; then
    if [ -f "$SCRIPT_DIR/../../../../wp-load.php" ]; then
        WP_PATH="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
    elif [ -f "./wp-load.php" ]; then
        WP_PATH="$(pwd)"
    fi
fi

if [ -z "$WP_PATH" ] || [ ! -f "$WP_PATH/wp-load.php" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [ERROR] WordPress installation not found at: ${WP_PATH:-<empty>}"
    echo "Usage: $0 /path/to/wordpress"
    exit 1
fi

# Detect wp-cli binary
WP_CMD="$(command -v wp || echo "")"
if [ -z "$WP_CMD" ] && [ -f "/usr/local/bin/wp" ]; then
    WP_CMD="/usr/local/bin/wp"
fi

if [ -z "$WP_CMD" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [ERROR] wp-cli ('wp') command not found in PATH."
    exit 1
fi

# Lockfile mechanism to prevent concurrent runs
LOCK_FILE="/tmp/ai_cv_tailor_autopilot.lock"
exec 200>"$LOCK_FILE"
if ! flock -n 200; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WARN] Another autopilot cron process is already running. Exiting."
    exit 0
fi

echo "================================================================================"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] [INFO] Starting AI CV Tailor Autopilot cron run..."
echo "[$(date '+%Y-%m-%d %H:%M:%S')] [INFO] WordPress path: $WP_PATH"
echo "================================================================================"

cd "$WP_PATH"

# Step 1: Fetch opportunities from all sources
echo "[$(date '+%Y-%m-%d %H:%M:%S')] [STEP 1/3] Fetching new job opportunities..."
$WP_CMD ai-cv-tailor autopilot fetch --path="$WP_PATH" || echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WARN] Fetch finished with warnings."

# Step 2: Analyze new opportunities with OpenAI
echo "[$(date '+%Y-%m-%d %H:%M:%S')] [STEP 2/3] Analyzing opportunities with OpenAI..."
$WP_CMD ai-cv-tailor autopilot analyze --path="$WP_PATH" || echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WARN] Analyze finished with warnings."

# Step 3: Generate tailored applications for matched opportunities
echo "[$(date '+%Y-%m-%d %H:%M:%S')] [STEP 3/3] Generating applications for matching opportunities..."
$WP_CMD ai-cv-tailor autopilot generate-applications --path="$WP_PATH" || echo "[$(date '+%Y-%m-%d %H:%M:%S')] [WARN] Application generation finished with warnings."

echo "================================================================================"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] [SUCCESS] AI CV Tailor Autopilot cron run completed."
echo "================================================================================"
