// js/functions.js - JavaScript Functionality

// Logout confirmation modal
function showLogoutModal() {
    // Remove existing modal if any
    const existing = document.getElementById('logoutModal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'logoutModal';
    modal.className = 'logout-modal';
    modal.innerHTML = `
        <div class="logout-modal-box">
            <div class="logout-icon">🚪</div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to log out of the system?</p>
            <div class="logout-modal-actions">
                <button class="btn-cancel-logout" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-confirm-logout" onclick="window.location.href='/SYSTEM/includes/logout.php'">Logout</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Close on backdrop click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeLogoutModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', function handler(e) {
        if (e.key === 'Escape') {
            closeLogoutModal();
            document.removeEventListener('keydown', handler);
        }
    });
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) modal.remove();
}

// Live table filter - filters table rows as user types
function liveTableFilter(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('input', function() {
        const filter = this.value.toLowerCase().trim();
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        const rows = tbody.querySelectorAll('tr');
        let visibleCount = 0;

        rows.forEach(function(row) {
            const cells = row.querySelectorAll('td');
            let match = false;
            cells.forEach(function(cell) {
                if (cell.textContent.toLowerCase().includes(filter)) {
                    match = true;
                }
            });
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Update counter if exists
        const counter = document.getElementById(tableId + '-count');
        if (counter) {
            counter.textContent = visibleCount;
        }
    });
}

// Initialize all live filters on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-init any live filter pairs found on the page
    var filters = document.querySelectorAll('[data-live-filter]');
    filters.forEach(function(input) {
        liveTableFilter(input.id, input.getAttribute('data-live-filter'));
    });
});

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

// Custom confirmation modal (replaces browser confirm dialogs)
function showConfirmModal(message, onConfirm) {
    // Remove any existing modal
    const existing = document.getElementById('customConfirmModal');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'customConfirmModal';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';

    const box = document.createElement('div');
    box.style.cssText = 'background:#fff;border-radius:8px;padding:0;min-width:350px;max-width:450px;box-shadow:0 4px 20px rgba(0,0,0,0.3);';

    box.innerHTML = `
        <div style="padding:20px 20px 10px;border-bottom:1px solid #eee;">
            <h3 style="margin:0;font-size:1.1em;">Confirm Action</h3>
        </div>
        <div style="padding:20px;">
            <p style="margin:0;color:#555;">${message}</p>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;gap:10px;justify-content:flex-end;">
            <button id="confirmModalCancel" style="padding:8px 18px;border:1px solid #ccc;background:#f5f5f5;border-radius:5px;cursor:pointer;font-size:0.95em;">Cancel</button>
            <button id="confirmModalOk" style="padding:8px 18px;border:none;background:#dc3545;color:#fff;border-radius:5px;cursor:pointer;font-size:0.95em;">Confirm</button>
        </div>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    document.getElementById('confirmModalCancel').onclick = function() {
        overlay.remove();
    };
    document.getElementById('confirmModalOk').onclick = function() {
        overlay.remove();
        if (onConfirm) onConfirm();
    };
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.remove();
    });
}

// Undrop modal with remarks textarea
function showUndropModal(dropId) {
    const existing = document.getElementById('undropModal');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'undropModal';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999;';

    const box = document.createElement('div');
    box.style.cssText = 'background:#fff;border-radius:8px;padding:0;min-width:400px;max-width:500px;box-shadow:0 4px 20px rgba(0,0,0,0.3);';

    box.innerHTML = `
        <div style="padding:20px 20px 10px;border-bottom:1px solid #eee;">
            <h3 style="margin:0;font-size:1.1em;">Undrop Class Card</h3>
        </div>
        <div style="padding:20px;">
            <p style="margin:0 0 12px;color:#555;">Are you sure you want to undrop this class card?</p>
            <label for="undropRemarks" style="display:block;margin-bottom:6px;font-weight:600;color:#333;font-size:0.95em;">Admin Remarks <span style="color:#999;font-weight:400;">(required)</span></label>
            <textarea id="undropRemarks" rows="4" placeholder="Enter reason for undropping..." style="width:100%;padding:10px;border:1px solid #ccc;border-radius:5px;font-size:0.95em;resize:vertical;box-sizing:border-box;"></textarea>
            <p id="undropRemarksError" style="color:#dc3545;font-size:0.85em;margin:4px 0 0;display:none;">Please enter remarks before proceeding.</p>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eee;display:flex;gap:10px;justify-content:flex-end;">
            <button id="undropModalCancel" style="padding:8px 18px;border:1px solid #ccc;background:#f5f5f5;border-radius:5px;cursor:pointer;font-size:0.95em;">Cancel</button>
            <button id="undropModalConfirm" style="padding:8px 18px;border:none;background:#dc3545;color:#fff;border-radius:5px;cursor:pointer;font-size:0.95em;">Confirm Undrop</button>
        </div>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    document.getElementById('undropModalCancel').onclick = function() {
        overlay.remove();
    };

    document.getElementById('undropModalConfirm').onclick = function() {
        const remarks = document.getElementById('undropRemarks').value.trim();
        const errorEl = document.getElementById('undropRemarksError');
        if (!remarks) {
            errorEl.style.display = 'block';
            document.getElementById('undropRemarks').focus();
            return;
        }
        // Set the remarks value in the hidden form and submit
        const form = document.getElementById('undropForm' + dropId);
        if (form) {
            const remarksInput = document.createElement('input');
            remarksInput.type = 'hidden';
            remarksInput.name = 'undrop_remarks';
            remarksInput.value = remarks;
            form.appendChild(remarksInput);
            form.submit();
        }
        overlay.remove();
    };

    document.getElementById('undropRemarks').addEventListener('input', function() {
        document.getElementById('undropRemarksError').style.display = 'none';
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.remove();
    });

    // Focus textarea
    setTimeout(function() {
        document.getElementById('undropRemarks').focus();
    }, 100);
}

// Confirm delete action
function confirmDelete(e) {
    if (e && e.preventDefault) e.preventDefault();
    const form = e && e.target ? e.target.closest('form') : null;
    showConfirmModal('Are you sure you want to delete this record?', function() {
        if (form) form.submit();
    });
    return false;
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

// Format date function (Philippines, 12-hour)
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PH', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
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
// Approve class card drop request
function approveDrop(dropId) {
    showConfirmModal('Are you sure you want to approve this class card drop?', function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/SYSTEM/includes/api.php?action=approve_drop';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'drop_id';
        input.value = dropId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    });
}

// Show approval modal
function showApprovalModal(dropId, studentName, subjectName) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'approvalModal';
    
    const content = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Approve Class Card Drop</h3>
                <button type="button" class="modal-close" onclick="closeApprovalModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Student:</strong> ${studentName}</p>
                <p><strong>Subject:</strong> ${subjectName}</p>
                <p>Are you sure you want to approve this class card drop request?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeApprovalModal()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmApproveDrop(${dropId})">Approve</button>
            </div>
        </div>
    `;
    
    modal.innerHTML = content;
    document.body.appendChild(modal);
    modal.style.display = 'block';
}

// Close approval modal
function closeApprovalModal() {
    const modal = document.getElementById('approvalModal');
    if (modal) {
        modal.remove();
    }
}

// Confirm approve drop
function confirmApproveDrop(dropId) {
    approveDrop(dropId);
    closeApprovalModal();
}