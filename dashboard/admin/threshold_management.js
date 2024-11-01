// Load Threshold Data and Populate the Form
async function loadThresholds() {
    const thresholdList = document.getElementById('threshold-list');
    thresholdList.innerHTML = ''; // Clear previous entries

    try {
        const response = await fetch('../../config/threshold_fetch.php');
        if (!response.ok) throw new Error("Failed to fetch threshold data.");

        const data = await response.json();

        data.forEach(threshold => {
            // Create a container for each device's thresholds
            const thresholdItem = document.createElement('div');
            thresholdItem.className = 'threshold-item';
            thresholdItem.innerHTML = `
                <h3>Device ID: ${threshold.device_id}</h3>
                <input type="hidden" name="device_id[]" value="${threshold.device_id}">
                <label>Smoke Threshold:
                    <input type="number" name="smoke_threshold[]" step="0.01" value="${threshold.smoke_threshold}">
                    <br>
                </label>
                <label>CO Threshold:
                    <input type="number" name="co_threshold[]" step="0.01" value="${threshold.co_threshold}">
                    <br>
                </label>
                <label>LPG Threshold:
                    <input type="number" name="lpg_threshold[]" step="0.01" value="${threshold.lpg_threshold}">
                    <br>
                </label>
                <hr>
            `;
            thresholdList.appendChild(thresholdItem); // Add each device's thresholds to the list
        });
    } catch (error) {
        console.error("Error loading thresholds:", error);
        document.getElementById('response-message').innerText = "Error loading thresholds.";
    }
}

// Submit the Form to Update Thresholds
async function updateThresholds(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('threshold-form'));

    try {
        const response = await fetch('../../config/threshold_update.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error("Failed to update thresholds.");

        const result = await response.json();
        document.getElementById('response-message').innerText = result.message;

        // Reload thresholds to show the updated values
        loadThresholds();
    } catch (error) {
        console.error("Error updating thresholds:", error);
        document.getElementById('response-message').innerText = "Failed to update thresholds.";
    }
}

// Event listener for form submission
document.getElementById('threshold-form').addEventListener('submit', updateThresholds);

// Load thresholds on page load
window.addEventListener('load', loadThresholds);
