// js/functions.js - JavaScript Functionality

// Update student info when selected
function updateStudentInfo() {
    const select = document.getElementById('student_id');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('course').value = selectedOption.dataset.course;
        document.getElementById('year').value = selectedOption.dataset.year;
    } else {
        document.getElementById('course').value = '';
        document.getElementById('year').value = '';
    }
}

// Update subject info when selected
function updateSubjectInfo() {
    const select = document.getElementById('subject_id');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        // Subject name is already shown in the option, nothing else to update
    }
}

// Confirm delete action
function confirmDelete() {
    return confirm('Are you sure you want to delete this record?');
}

// Show notification message
function showNotification(type, message) {
    const div = document.createElement('div');
    div.className = `alert alert-${type}`;
    div.textContent = message;
    
    const container = document.querySelector('.content-wrapper');
    if (container) {
        container.insertBefore(div, container.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            div.remove();
        }, 5000);
    }
}

// Format date function
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
    console.log('Page loaded');
});

// Export current page as table to CSV
function exportTableToCSV(filename = 'export.csv') {
    const table = document.querySelector('table');
    if (!table) return;
    
    let csv = [];
    let rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        let csvRow = [];
        let cells = row.querySelectorAll('td, th');
        
        cells.forEach(cell => {
            csvRow.push('"' + cell.innerText + '"');
        });
        
        csv.push(csvRow.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Print page function
function printPage() {
    window.print();
}
