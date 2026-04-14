<?php
session_start();

require '../db_connect.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

// Helper function for extracting text from nested tables
function extractTextFromNestedTable($table) {
  $text = "";
  foreach ($table->getRows() as $row) {
    foreach ($row->getCells() as $cell) {
      foreach ($cell->getElements() as $element) {
        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
          $text .= $element->getText() . " ";
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
          foreach ($element->getElements() as $textRunElement) {
            if ($textRunElement instanceof \PhpOffice\PhpWord\Element\Text) {
              $text .= $textRunElement->getText() . " ";
            }
          }
        }
      }
    }
  }
  return $text;
}

// Alternative method for extracting tables from Word documents
function extractTablesAlternative($phpWord) {
  $data = [];
  $rowIndex = 0;
  
  // Process all sections in the document
  foreach ($phpWord->getSections() as $section) {
    // Process all elements in the section
    foreach ($section->getElements() as $element) {
      // Special handling for tables
      if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
        // Get all rows from the table
        $rows = $element->getRows();
        $columnCount = 0;
        
        // Find maximum number of columns
        foreach ($rows as $row) {
          $columnCount = max($columnCount, count($row->getCells()));
        }
        
        // Process each row
        foreach ($rows as $row) {
          $cellData = array_fill(0, $columnCount, '');
          $cells = $row->getCells();
          
          // Process each cell in the row
          for ($i = 0; $i < count($cells); $i++) {
            $cell = $cells[$i];
            $text = '';
            
            // Process all elements in the cell
            foreach ($cell->getElements() as $cellElement) {
              if ($cellElement instanceof \PhpOffice\PhpWord\Element\Text) {
                $text .= $cellElement->getText();
              } elseif ($cellElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($cellElement->getElements() as $textRunElement) {
                  if ($textRunElement instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $textRunElement->getText();
                  }
                }
              }
            }
            
            $cellData[$i] = $text;
          }
          
          // Add row to data array if it contains any content
          $hasContent = false;
          foreach ($cellData as $value) {
            if (!empty(trim($value))) {
              $hasContent = true;
              break;
            }
          }
          
          if ($hasContent) {
            $data[$rowIndex] = $cellData;
            $rowIndex++;
          }
        }
      }
    }
  }
  
  return $data;
}

// Handle file upload and preview
if (isset($_POST['preview'])) {
  if ($_FILES['file']['name']) {
    // Create a temporary file with a more permanent location
    $upload_dir = '../upload/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }

    $original_filename = basename($_FILES['file']['name']);
    $temp_file = $upload_dir . $original_filename;
    move_uploaded_file($_FILES['file']['tmp_name'], $temp_file);

    $file_path = $temp_file;
    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $file_type = $file_extension;

    if ($file_extension == 'xlsx' || $file_extension == 'xls') {
      // Handle Excel file
      $reader = new Xlsx();
      $spreadsheet = $reader->load($file_path);
      $worksheet = $spreadsheet->getActiveSheet();
      $data = $worksheet->toArray();

      // Store data for preview
      $preview_data = $data;
    } elseif ($file_extension == 'docx' || $file_extension == 'doc') {
      // Improved Word file handling for large documents
      try {
        // Use mammoth to extract text as a fallback if PhpWord can't handle tables
        $phpWord = WordIOFactory::load($file_path);
        $data = [];
        $rowIndex = 0;
        
        // Extract tables from Word with improved handling
        $sections = $phpWord->getSections();
        foreach ($sections as $section) {
          $elements = $section->getElements();
          foreach ($elements as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
              foreach ($element->getRows() as $row) {
                $cellIndex = 0;
                $rowData = [];
                foreach ($row->getCells() as $cell) {
                  $text = '';
                  foreach ($cell->getElements() as $cellElement) {
                    if ($cellElement instanceof \PhpOffice\PhpWord\Element\Text) {
                      $text .= $cellElement->getText();
                    } elseif ($cellElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
                      foreach ($cellElement->getElements() as $textRunElement) {
                        if ($textRunElement instanceof \PhpOffice\PhpWord\Element\Text) {
                          $text .= $textRunElement->getText();
                        }
                      }
                    } elseif ($cellElement instanceof \PhpOffice\PhpWord\Element\Table) {
                      // Handle nested tables (recursive)
                      $text .= extractTextFromNestedTable($cellElement);
                    }
                  }
                  $rowData[$cellIndex] = $text;
                  $cellIndex++;
                }
                // Only add non-empty rows (at least one cell must have content)
                $hasContent = false;
                foreach ($rowData as $cellContent) {
                  if (!empty(trim($cellContent))) {
                    $hasContent = true;
                    break;
                  }
                }
                if ($hasContent) {
                  $data[$rowIndex] = $rowData;
                  $rowIndex++;
                }
              }
            }
          }
        }

        // If no data was extracted, try a different approach
        if (empty($data)) {
          error_log("No tables found using primary method. Trying alternative method.");
          // Alternative extraction method
          $data = extractTablesAlternative($phpWord);
        }

        $preview_data = $data;
      } catch (Exception $e) {
        error_log("Error processing Word file: " . $e->getMessage());
        echo "<div class='alert-message error'>Error processing Word file: " . $e->getMessage() . "</div>";
      }
    } else {
      echo "<div class='alert-message error'>Unsupported file format. Please upload Excel or Word files only.</div>";
    }

    // Store the file info in session for later use
    $_SESSION['file_path'] = $file_path;
    $_SESSION['file_name'] = $original_filename;
    $_SESSION['file_type'] = $file_type;
    $_SESSION['preview_data'] = $preview_data;
  } else {
    echo "<div class='alert-message error'>No file uploaded!</div>";
  }
}

// Inside the save_to_db handler section, modify it like this:
if (isset($_POST['save_to_db'])) {
    // Get the data from the form
    $edited_data = [];
    $success_count = 0;
    $error_count = 0;
    $batch_id = time(); // Use timestamp as batch ID
    $file_deleted = false;

    if (isset($_POST['data']) && is_array($_POST['data'])) {
        foreach ($_POST['data'] as $index => $row) {
            if (isset($row['selected']) && $row['selected'] == 'on') {
                $edited_data[] = [
                    $row['call_no'] ?? '',
                    $row['accession_no'] ?? '',
                    $row['author_title'] ?? '',
                    $row['title'] ?? '',
                    $row['volume'] ?? ''
                ];
            }
        }
    }

    // Begin transaction
    $conn->begin_transaction();
    
    try {
        foreach ($edited_data as $index => $row) {
            // Skip empty rows
            if (empty($row[0]) && empty($row[1]) && empty($row[2]) && empty($row[3]) && empty($row[4])) {
                continue;
            }

            // Read values safely with proper type conversion
            $call_no = trim($row[0]);
            $accession_no = trim($row[1]);
            $author_title = trim($row[2]);
            $title = trim($row[3]);
            $volume = trim($row[4]);

            // Insert data into database
            $stmt = $conn->prepare("INSERT INTO import (batch_id, call_no, accession_no, author_title, title, volume, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) {
                throw new Exception("Error in SQL: " . $conn->error);
            }
            $stmt->bind_param("isssss", $batch_id, $call_no, $accession_no, $author_title, $title, $volume);

            if ($stmt->execute()) {
                $success_count++;
            } else {
                throw new Exception("Error inserting row " . ($index + 1) . ": " . $stmt->error);
            }
        }

        // If we get here, all inserts were successful
        $conn->commit();

        // Delete the uploaded file if it exists
        if (isset($_SESSION['file_path']) && file_exists($_SESSION['file_path'])) {
            if (unlink($_SESSION['file_path'])) {
                $file_deleted = true;
            } else {
                error_log("Failed to delete file: " . $_SESSION['file_path']);
            }
        }

        // Clear session data
        unset($_SESSION['preview_data']);
        unset($_SESSION['file_path']);
        unset($_SESSION['file_name']);
        unset($_SESSION['file_type']);

        $message = "Import Successful! Imported $success_count records.";
        if ($file_deleted) {
            $message .= " Uploaded file has been removed.";
        }
        echo "<div class='alert-message success'>$message</div>";

    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $conn->rollback();
        $error_count++;
        echo "<div class='alert-message error'>Error: " . $e->getMessage() . "</div>";
        error_log("Import Error: " . $e->getMessage());
    }
}

if (isset($_POST['save_batch'])) {
    $selected_rows = [];
    $success_count = 0;
    $error_count = 0;
    $batch_id = time();
    $batch_size = intval($_POST['batch_size'] ?? 100);
    $current_batch = intval($_POST['current_batch'] ?? 1);
    $remaining_rows = 0;

    if (isset($_POST['data']) && is_array($_POST['data'])) {
        foreach ($_POST['data'] as $index => $row) {
            if (isset($row['selected']) && $row['selected'] == 'on') {
                $selected_rows[] = [
                    $row['call_no'] ?? '',
                    $row['accession_no'] ?? '',
                    $row['author_title'] ?? '',
                    $row['title'] ?? '',
                    $row['volume'] ?? ''
                ];
            }
        }
    }

    $total_rows = count($selected_rows);
    $start_index = ($current_batch - 1) * $batch_size;
    $end_index = min($start_index + $batch_size, $total_rows);
    $batch_rows = array_slice($selected_rows, $start_index, $batch_size);
    
    $conn->begin_transaction();
    
    try {
        foreach ($batch_rows as $index => $row) {
            if (empty($row[0]) && empty($row[1]) && empty($row[2]) && empty($row[3]) && empty($row[4])) {
                continue;
            }

            $call_no = trim($row[0]);
            $accession_no = trim($row[1]);
            $author_title = trim($row[2]);
            $title = trim($row[3]);
            $volume = trim($row[4]);

            $stmt = $conn->prepare("INSERT INTO import (batch_id, call_no, accession_no, author_title, title, volume, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) {
                throw new Exception("Error in SQL: " . $conn->error);
            }
            
            $stmt->bind_param("isssss", $batch_id, $call_no, $accession_no, $author_title, $title, $volume);

            if ($stmt->execute()) {
                $success_count++;
            } else {
                throw new Exception("Error inserting row " . ($start_index + $index + 1) . ": " . $stmt->error);
            }
        }

        $conn->commit();
        $remaining_rows = $total_rows - $end_index;

        if ($success_count > 0 && $remaining_rows == 0) {
            if (isset($_SESSION['file_path']) && file_exists($_SESSION['file_path'])) {
                if (unlink($_SESSION['file_path'])) {
                    echo "<div class='alert-message success'>All data imported successfully! Uploaded file has been removed.</div>";
                } else {
                    echo "<div class='alert-message success'>All data imported successfully! Note: Could not remove uploaded file.</div>";
                    error_log("Failed to delete file: " . $_SESSION['file_path']);
                }
            }
            
            unset($_SESSION['preview_data']);
            unset($_SESSION['file_path']);
            unset($_SESSION['file_name']);
            unset($_SESSION['file_type']);
        } else {
            echo "<div class='alert-message success'>Batch $current_batch imported successfully! Imported $success_count records.</div>";
            if ($remaining_rows > 0) {
                echo "<div class='alert-message info'>$remaining_rows rows remaining to be processed.</div>";
            }
        }

    } catch (Exception $e) {
        $conn->rollback();
        $error_count++;
        echo "<div class='alert-message error'>Error: " . $e->getMessage() . "</div>";
        error_log("Batch Import Error: " . $e->getMessage());
    }
}

// Add new blank row
if (isset($_POST['add_blank_row'])) {
  if (isset($_SESSION['preview_data']) && is_array($_SESSION['preview_data'])) {
    // Add a new blank row to preview data
    $_SESSION['preview_data'][] = ['', '', '', '', ''];
    $preview_data = $_SESSION['preview_data'];
  } else {
    // If no preview data exists, create a new one with header and blank row
    $_SESSION['preview_data'] = [
      ['CALL NO.', 'ACCESSION NO.', 'AUTHOR/TITLE', 'TITLE', 'VOLUME'],
      ['', '', '', '', '']
    ];
    $preview_data = $_SESSION['preview_data'];
  }
}

// Load data from session or database depending on context
if (!empty($_SESSION['preview_data'])) {
  $preview_data = $_SESSION['preview_data'];
  $file_type = $_SESSION['file_type'] ?? '';

  // Filter data by search if needed
  if (!empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
    $filtered_data = [array_shift($preview_data)]; // Keep header row

    foreach ($preview_data as $row) {
      $row_text = implode(' ', $row);
      if (stripos($row_text, $search_query) !== false) {
        $filtered_data[] = $row;
      }
    }

    $preview_data = $filtered_data;
  }
} else if (isset($_GET['batch_id']) && $_GET['batch_id'] > 0) {
  $batch_id = intval($_GET['batch_id']);
  // Load data from specific batch in database
  $query = "SELECT * FROM import WHERE batch_id = ?";
  $params = [$batch_id];

  if (!empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
    $query .= " AND (call_no LIKE ? OR accession_no LIKE ? OR author_title LIKE ? OR title LIKE ? OR volume LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
  }

  $query .= " ORDER BY id ASC";

  $stmt = $conn->prepare($query);
  $stmt->bind_param(str_repeat("s", count($params)), ...$params);
  $stmt->execute();
  $result = $stmt->get_result();

  // Format database results to match preview data structure
  $preview_data = [['CALL NO.', 'ACCESSION NO.', 'AUTHOR/TITLE', 'TITLE', 'VOLUME']]; // Header row

  while ($row = $result->fetch_assoc()) {
    $preview_data[] = [
      $row['call_no'],
      $row['accession_no'],
      $row['author_title'],
      $row['title'],
      $row['volume']
    ];
  }
}

// Handle clear preview data but don't delete from database
if (isset($_POST['clear_preview'])) {
  $preview_data = [];
  unset($_SESSION['preview_data']);
}

// Handle row deletion from preview
if (isset($_POST['delete_selected'])) {
  if (isset($_POST['data']) && is_array($_POST['data'])) {
    // Start with the header row
    $filtered_data = [$_SESSION['preview_data'][0]];

    foreach ($_SESSION['preview_data'] as $index => $row) {
      if ($index === 0) continue; // Skip header row

      $row_index = $index - 1; // Adjust for header row offset
      if (!isset($_POST['data'][$row_index]['selected']) || $_POST['data'][$row_index]['selected'] !== 'on') {
        $filtered_data[] = $row;
      }
    }

    $_SESSION['preview_data'] = $filtered_data;
    $preview_data = $_SESSION['preview_data'];
  }
}

// Handle batch deletion
if (isset($_POST['delete_batch']) && isset($_POST['batch_id']) && $_POST['batch_id'] > 0) {
  $delete_batch_id = intval($_POST['batch_id']);
  $stmt = $conn->prepare("DELETE FROM import WHERE batch_id = ?");
  $stmt->bind_param("i", $delete_batch_id);

  if ($stmt->execute()) {
    echo "<div class='alert-message success'>Batch deleted successfully!</div>";
  } else {
    echo "<div class='alert-message error'>Error deleting batch: " . $stmt->error . "</div>";
  }
}

// Prepare data for display (no pagination)
if (!empty($preview_data)) {
  // First entry is header row
  $header_row = $preview_data[0];
  $data_rows = array_slice($preview_data, 1);

  // Reconstruct preview data with header and all rows
  $display_data = [$header_row];
  foreach ($data_rows as $row) {
    $display_data[] = $row;
  }
} else {
  $display_data = [];
}

require '_layout.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<div class="main-content">
  <div class="container">
  <div class="card">
  <div class="nav-actions">
    <div class="card-header">
      <h4><i class="fas fa-file-import"></i> Import Excel or Word</h4>
    </div>
    <div>
      <a href="manage-file.php" class="card-link">
        <i class="fas fa-table"></i> Manage Records
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="action-section">
      <h3><i class="fas fa-upload"></i> File Upload</h3>
      <form action="" method="post" enctype="multipart/form-data" id="uploadForm">
        <div class="form-group">
          <label for="file">Select File:</label>
          <input type="file" name="file" id="fileInput" accept=".xlsx,.xls,.docx,.doc" required style="display: none;">
          <div id="drop-area" class="drop-zone">
            <div class="drop-zone-text">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Drag and drop your file here</p>
              <p>- or -</p>
              <button type="button" id="browse-button" class="browse-btn">Browse Files</button>
            </div>
            <div id="file-info" class="file-info" style="display: none;">
              <i class="fas fa-file-alt file-icon"></i>
              <span id="file-name">No file selected</span>
              <button type="button" id="remove-file" class="remove-btn">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
        <button type="submit" name="preview">
          <i class="fas fa-eye"></i> Preview Data
        </button>
      </form>
    </div>
  </div>
</div>
    <?php
    if (empty($preview_data) && !isset($_GET['batch_id'])) {
      $preview_data = [
          ['CALL NO.', 'ACCESSION NO.', 'AUTHOR/TITLE', 'TITLE', 'VOLUME']
      ];
      $display_data = $preview_data;
  }?>
    <!-- Preview & Edit Table -->
    <?php if (!empty($preview_data)): ?>
      <div class="card">
    <div class="card-header">
        <h4><i class="fas fa-table"></i> Data Preview & Edit</h4>
    </div>
    <div class="card-body">
        <div class="file-info">
            <i class="fas fa-info-circle"></i>
            <span>Total Records: </span> <?php echo (isset($preview_data) && count($preview_data) > 1) ? count($preview_data) - 1 : 0; ?>
        </div>
        
        <div class="search-container">
            <input type="text" id="search-input" placeholder="Search records..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button id="search-button">
                <i class="fas fa-search" title="Search"></i>
            </button>
            <button id="clear-search">
                <i class="fas fa-undo" title="Clear Search"></i>
            </button>
        </div>
        <br>
        <form action="" method="post" id="data-form">
            <div class="table-header-actions">
                <button type="submit" name="save_to_db" title="Save all data to the database">
                    <i class="fas fa-save"></i> Save
                </button>
                <button type="submit" name="clear_preview" title="Clear preview data">
                    <i class="fas fa-eraser"></i>
                </button>
                <button type="button" id="open-batch-modal" title="Open batch processing modal">
                    <i class="fas fa-layer-group"></i> 
                </button>
                <button type="submit" name="add_blank_row" title="Add a blank row">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="submit" name="delete_selected" title="Delete selected items">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <p>"Note: You must select all records before saving to the database"</p>
            </div>


            <div class="select-all-container">
                <input type="checkbox" id="select-all"> 
                <label for="select-all">Select All Records</label>
            </div>

            <div class="table-container">
                <div class="table-loader" style="display: none;">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="scrollable-table">
                    <table>
                        <thead>
                            <tr>
                                <th width="40"><i class="fas fa-check-square"></i></th>
                                <th>CALL NO.</th>
                                <th>ACCESSION NO.</th>
                                <th>AUTHOR/TITLE</th>
                                <th>TITLE</th>
                                <th>VOLUME</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($display_data) && count($display_data) > 1): ?>
                                <?php foreach (array_slice($display_data, 1) as $index => $row): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="data[<?php echo $index; ?>][selected]">
                                        </td>
                                        <td class="editable">
                                            <input type="text" name="data[<?php echo $index; ?>][call_no]"
                                                value="<?php echo htmlspecialchars($row[0] ?? ''); ?>">
                                        </td>
                                        <td class="editable">
                                            <input type="text" name="data[<?php echo $index; ?>][accession_no]"
                                                value="<?php echo htmlspecialchars($row[1] ?? ''); ?>">
                                        </td>
                                        <td class="editable author-title">
                                            <input type="text" name="data[<?php echo $index; ?>][author_title]"
                                                value="<?php echo htmlspecialchars($row[2] ?? ''); ?>">
                                        </td>
                                        <td class="editable">
                                            <input type="text" name="data[<?php echo $index; ?>][title]"
                                                value="<?php echo htmlspecialchars($row[3] ?? ''); ?>">
                                        </td>
                                        <td class="editable">
                                            <input type="text" name="data[<?php echo $index; ?>][volume]"
                                                value="<?php echo htmlspecialchars($row[4] ?? ''); ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="empty-table-message">
                                    <td colspan="6" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-file-import fa-3x"></i>
                                            <p>No records found. Upload a file or click the "Add a blank row" button to start.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>

      <!-- Batch Import Modal -->
      <div id="batch-modal" class="modal" style="display: none;">
        <div class="modal-content">
          <span class="close">&times;</span>
          <h2><i class="fas fa-layer-group"></i> Import by Batch</h2>
          <form action="" method="post" id="batch-form">
            <div class="form-group">
              <label for="batch-size">Batch Size:</label>
              <input type="number" name="batch_size" id="batch-size" value="100" min="1">
            </div>
            <div class="form-group">
              <label for="current-batch">Current Batch:</label>
              <input type="number" name="current_batch" id="current-batch" value="1" min="1">
            </div>
            <input type="hidden" name="save_batch" value="1">
            <div id="batch-selected-count"></div>
            <button type="submit" id="import-batch-btn">
              <i class="fas fa-file-import"></i> Import Batch
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Select all functionality
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name^="data"][name$="[selected]"]');
        checkboxes.forEach(checkbox => {
          checkbox.checked = selectAllCheckbox.checked;
        });
      });
    }

    // Search functionality with loading animation
    const searchButton = document.getElementById('search-button');
    const clearSearch = document.getElementById('clear-search');
    const searchInput = document.getElementById('search-input');
    const tableLoader = document.querySelector('.table-loader');

    if (searchButton && searchInput) {
      searchButton.addEventListener('click', function() {
        // Show loading animation
        showLoader();
        window.location.href = window.location.pathname + '?search=' + encodeURIComponent(searchInput.value);
      });

      searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          // Show loading animation
          showLoader();
          window.location.href = window.location.pathname + '?search=' + encodeURIComponent(searchInput.value);
        }
      });
    }

    if (clearSearch) {
      clearSearch.addEventListener('click', function() {
        // Show loading animation
        showLoader();
        window.location.href = window.location.pathname;
      });
    }

    // Delete row functionality
    const deleteRowButtons = document.querySelectorAll('.delete-row');
    deleteRowButtons.forEach(button => {
      button.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this row?')) {
          const rowIndex = this.getAttribute('data-index');
          const checkbox = document.querySelector(`input[name="data[${rowIndex}][selected]"]`);
          checkbox.checked = true;

          // Show loading animation
          showLoader();
          document.getElementById('data-form').submit();
        }
      });
    });

    // Batch import modal
    const batchModal = document.getElementById('batch-modal');
    const openBatchModal = document.getElementById('open-batch-modal');
    const closeSpan = document.querySelector('.close');

    if (openBatchModal && batchModal) {
      openBatchModal.addEventListener('click', function() {
        // Count selected rows
        const selectedCheckboxes = document.querySelectorAll('input[name^="data"][name$="[selected]"]:checked');
        const batchSelectedCount = document.getElementById('batch-selected-count');

        if (selectedCheckboxes.length === 0) {
          alert('Please select at least one row to import.');
          return;
        }

        if (batchSelectedCount) {
          batchSelectedCount.textContent = `${selectedCheckboxes.length} rows selected for import`;
        }

        // Clone selected checkboxes to batch form
        const batchForm = document.getElementById('batch-form');
        selectedCheckboxes.forEach(checkbox => {
          const clonedCheckbox = checkbox.cloneNode(true);
          clonedCheckbox.style.display = 'none';
          batchForm.appendChild(clonedCheckbox);

          // Clone associated input fields
          const rowIndex = checkbox.name.match(/data\[(\d+)\]/)[1];
          const rowInputs = document.querySelectorAll(`input[name^="data[${rowIndex}]"][name$="]"]:not([name$="[selected]"])`);
          rowInputs.forEach(input => {
            const clonedInput = input.cloneNode(true);
            clonedInput.style.display = 'none';
            batchForm.appendChild(clonedInput);
          });
        });

        batchModal.style.display = 'block';
      });
    }

    if (closeSpan) {
      closeSpan.addEventListener('click', function() {
        batchModal.style.display = 'none';

        // Remove cloned elements
        const hiddenInputs = document.querySelectorAll('#batch-form input[style="display: none;"]');
        hiddenInputs.forEach(input => input.remove());
      });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target === batchModal) {
        batchModal.style.display = 'none';

        // Remove cloned elements
        const hiddenInputs = document.querySelectorAll('#batch-form input[style="display: none;"]');
        hiddenInputs.forEach(input => input.remove());
      }
    });

    // Import batch form submission with loading animation
    const batchForm = document.getElementById('batch-form');
    if (batchForm) {
      batchForm.addEventListener('submit', function(e) {
        // Copy selected checkboxes from main form
        const dataForm = document.getElementById('data-form');
        const selectedCheckboxes = dataForm.querySelectorAll('input[name^="data"][name$="[selected]"]:checked');

        if (selectedCheckboxes.length === 0) {
          e.preventDefault();
          alert('Please select at least one row to import.');
          return;
        }

        // Show loading animation
        showLoader();
      });
    }

    // Add loading animation to main form submission
    const dataForm = document.getElementById('data-form');
    if (dataForm) {
      dataForm.addEventListener('submit', function() {
        // Show loading animation
        showLoader();
      });
    }
// Disable save button when no rows are selected
const saveToDbButton = document.querySelector('button[name="save_to_db"]');
const dataCheckboxes = document.querySelectorAll('input[name^="data"][name$="[selected]"]');

function updateSaveButtonState() {
  const anySelected = Array.from(document.querySelectorAll('input[name^="data"][name$="[selected]"]')).some(checkbox => checkbox.checked);
  if (saveToDbButton) {
    saveToDbButton.disabled = !anySelected;
    saveToDbButton.style.opacity = anySelected ? '1' : '0.5';
    saveToDbButton.style.cursor = anySelected ? 'pointer' : 'not-allowed';
  }
}

// Initial button state
if (saveToDbButton && dataCheckboxes.length > 0) {
  updateSaveButtonState();
  
  // Update button state when any checkbox changes
  dataCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateSaveButtonState);
  });
  
  // Also update when select-all changes
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', updateSaveButtonState);
  }
}

    // Function to show loader
    function showLoader() {
      if (tableLoader) {
        tableLoader.style.display = 'flex';
      }
    }

    // Listen for beforeunload event to show loader on page refresh
    window.addEventListener('beforeunload', function() {
      showLoader();
    });
  });
  document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('fileInput');
    const browseButton = document.getElementById('browse-button');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const removeFileBtn = document.getElementById('remove-file');
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      dropArea.addEventListener(eventName, preventDefaults, false);
      document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
      dropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
      dropArea.addEventListener(eventName, unhighlight, false);
    });
    
    // Handle dropped files
    dropArea.addEventListener('drop', handleDrop, false);
    
    // Handle browse button click
    browseButton.addEventListener('click', () => {
      fileInput.click();
    });
    
    // Handle file selection via input
    fileInput.addEventListener('change', handleFiles);
    
    // Handle remove file button
    removeFileBtn.addEventListener('click', () => {
      fileInput.value = '';
      fileInfo.style.display = 'none';
      dropArea.querySelector('.drop-zone-text').style.display = 'block';
    });
    
    function preventDefaults(e) {
      e.preventDefault();
      e.stopPropagation();
    }
    
    function highlight() {
      dropArea.classList.add('dragover');
    }
    
    function unhighlight() {
      dropArea.classList.remove('dragover');
    }
    
    function handleDrop(e) {
      const dt = e.dataTransfer;
      const files = dt.files;
      handleFiles({target: {files: files}});
    }
    
    function handleFiles(e) {
      const files = e.target.files;
      if (files.length) {
        fileInput.files = files;
        updateFileInfo(files[0]);
      }
    }
    
    function updateFileInfo(file) {
      fileName.textContent = file.name;
      fileInfo.style.display = 'flex';
      dropArea.querySelector('.drop-zone-text').style.display = 'none';
      
      // Set appropriate icon based on file type
      const fileIcon = document.querySelector('.file-icon');
      if (file.name.match(/\.(xlsx|xls)$/i)) {
        fileIcon.className = 'fas fa-file-excel file-icon';
      } else if (file.name.match(/\.(docx|doc)$/i)) {
        fileIcon.className = 'fas fa-file-word file-icon';
      }
    }
  });
</script>
<style>
  /* Professional Data Management Styles */

  /* Base styles and variables */
  :root {
    --primary-color: #004d40;
    --primary-light: #34495e;
    --secondary-color: #3498db;
    --secondary-light: #5dade2;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --info-color: #3498db;
    --text-color: #333;
    --light-text: #ecf0f1;
    --border-color: #ddd;
    --light-bg: #f8f9fa;
    --card-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
  }
  .drop-zone {
    border: 2px dashed #ccc;
    border-radius: 6px;
    padding: 25px;
    text-align: center;
    margin-bottom: 20px;
    cursor: pointer;
    transition: all 0.3s;
  }
  .drop-zone.dragover {
    background-color: #f0f8ff;
    border-color:v #004d40;
  }
  .drop-zone-text {
    color: #666;
  }
  .drop-zone-text i {
    font-size: 48px;
    color: #999;
    margin-bottom: 10px;
  }
  .browse-btn {
    background-color: #004d40;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 10px;
  }
  .file-info {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
  }
  .file-icon {
    font-size: 24px;
    margin-right: 10px;
    color: #007bff;
  }
  .remove-btn {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 16px;
    margin-left: 10px;
  }
  .empty-state {
    padding: 40px 20px;
    text-align: center;
    color: #7f8c8d;
}

.empty-state i {
    margin-bottom: 15px;
    color: #bdc3c7;
}

.empty-state p {
    font-size: 16px;
    margin-bottom: 0;
}

.text-center {
    text-align: center;
}
  /* Container and layout */
  .container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
  }

  .main-content {
    padding: 20px 0;
  }

  /* Card styles */
  .card {
    background: #fff;
    border-radius: 6px;
    box-shadow: var(--card-shadow);
    margin-bottom: 25px;
    border: none;
    overflow: hidden;
  }

  .card-header {
    padding: 15px 20px;
    background-color: var(--primary-color);
    color: var(--light-text);
    border-bottom: 1px solid var(--primary-light);
    display: flex;
    align-items: center;
  }

  .card-header h4 {
    margin: 0;
    font-weight: 500;
    font-size: 1.1rem;
  }

  .card-header .fa {
    margin-right: 10px;
  }

  .card-body {
    padding: 20px;
  }

  /* Navigation and actions */
  .nav-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
  }

  .action-section {
    margin-bottom: 20px;
  }

  .action-section h3 {
    font-size: 1.1rem;
    color: var(--primary-color);
    margin-bottom: 15px;
    font-weight: 500;
  }

  .card-link {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    background-color: var(--secondary-color);
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    font-weight: 500;
    transition: var(--transition);
  }

  .card-link:hover {
    background-color: var(--secondary-light);
    text-decoration: none;
  }

  .card-link .fa {
    margin-right: 8px;
  }

  /* Form elements */
  .form-group {
    margin-bottom: 15px;
  }

  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--primary-color);
  }

  input[type="text"],
  input[type="number"],
  input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 14px;
    transition: var(--transition);
  }

  input[type="text"]:focus,
  input[type="number"]:focus,
  input[type="file"]:focus {
    border-color: var(--secondary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
  }

  /* Buttons */
  button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    gap: 10px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: var(--transition);
    font-size: 14px;
  }

  button:hover {
    background-color: var(--primary-light);
  }

  button .fa {
    margin-right: 15px;
  }

  button[name="preview"] {
    background-color: var(--secondary-color);
  }

  button[name="preview"]:hover {
    background-color: var(--secondary-light);
  }

  button[name="save_to_db"] {
    background-color: var(--success-color);
  }

  button[name="save_to_db"]:hover {
    background-color: #2ecc71;
  }

  button[name="clear_preview"] {
    background-color: var(--warning-color);
  }

  button[name="clear_preview"]:hover {
    background-color: #f1c40f;
  }

  button[name="delete_selected"] {
    background-color: var(--danger-color);
  }

  button[name="delete_selected"]:hover {
    background-color: #c0392b;
  }

  /* Table styles */
  .table-container {
    position: relative;
    margin-top: 20px;
    border-radius: 4px;
    border: 1px solid var(--border-color);
    overflow: hidden;
  }

  .scrollable-table {
    overflow-x: auto;
    max-height: 600px;
    overflow-y: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
  }

  table th,
  table td {
    border: 1px solid var(--border-color);
    padding: 12px 15px;
  }

  table th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: var(--primary-color);
    color: white;
    font-weight: 500;
    position: sticky;
    top: 0;
    z-index: 1;
  }

  table tr:nth-child(even) {
    background-color: #f2f7ff;
  }

  table tr:hover {
    background-color: #e8f4fc;
  }

  .editable input {
    width: 100%;
    padding: 8px;
    border: 1px solid #e0e0e0;
    border-radius: 3px;
    transition: all 0.3s;
  }

  .editable input:focus {
    border-color: var(--secondary-color);
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
  }

  /* Checkbox styling */
  input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--primary-color);
  }

  .select-all-container {
    margin: 10px 0;
    display: flex;
    align-items: center;
  }

  .select-all-container label {
    margin-left: 5px;
    font-weight: 500;
    cursor: pointer;
  }

  /* Search container */
  .search-container {
    display: flex;
    margin-top: 10px;
    gap: 5px;
  }

  .search-container input {
    flex-grow: 1;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
  }

  /* File info */
  .file-info {
    display: flex;
    align-items: center;
    margin: 10px 0;
    color: var(--primary-color);
    font-weight: 500;
  }

  .file-info span {
    font-weight: 600;
  }

  /* Table header actions */
  .table-header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
  }

  /* Modal styles */
  .modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background-color: white;
    padding: 25px;
    border-radius: 6px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 500px;
    position: relative;
    animation: modalFadeIn 0.3s;
  }

  @keyframes modalFadeIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .close {
    position: absolute;
    right: 15px;
    top: 15px;
    color: #aaa;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
  }

  .close:hover {
    color: var(--danger-color);
  }

  .modal-content h2 {
    margin-top: 0;
    color: var(--primary-color);
    font-size: 1.4rem;
    font-weight: 500;
  }

  #batch-selected-count {
    margin: 15px 0;
    font-weight: 500;
    color: var(--primary-color);
  }

  /* Alert messages */
  .alert-message {
    padding: 12px 15px;
    margin: 15px 320px;
    border-radius: 4px;
    font-weight: 500;
    display: flex;
    align-items: center;
  }

  .alert-message::before {
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    margin-right: 10px;
    font-size: 16px;
  }

  .error {
    background-color: #fee2e2;
    color: #b91c1c;
    border-left: 4px solid #ef4444;
  }

  .error::before {
    content: "\f06a";
  }

  .success {
    background-color: #dcfce7;
    color: #166534;
    border-left: 4px solid #22c55e;
  }

  .success::before {
    content: "\f00c";
  }

  .info {
    background-color: #dbeafe;
    color: #1e40af;
    border-left: 4px solid #3b82f6;
  }

  .info::before {
    content: "\f05a";
  }
  p {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
    color: gray;

  }
  /* Responsive adjustments */
  @media (max-width: 768px) {
    .table-header-actions {
      flex-direction: column;
      align-items: stretch;
    }

    .table-header-actions button {
      margin-bottom: 5px;
    }

    .search-container {
      flex-direction: column;
    }

    .search-container button {
      margin-top: 5px;
    }

    .card-header {
      flex-direction: column;
      align-items: flex-start;
    }

    .nav-actions {
      flex-direction: column;
      align-items: flex-start;
    }

    .nav-actions>div:last-child {
      margin-top: 10px;
    }
  }

  /* Loading spinner improvements */
  .table-loader {
    display: none;
    justify-content: center;
    align-items: center;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.7);
    z-index: 10;
  }

  .spinner-border {
    width: 3rem;
    height: 3rem;
    border: 0.25em solid var(--secondary-color);
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner 0.75s linear infinite;
  }

  @keyframes spinner {
    to {
      transform: rotate(360deg);
    }
  }

  .visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  /* Additional styles for better loading experience */
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
  }

  .loading-overlay.active {
    display: flex;
  }

  .loading-message {
    color: white;
    font-weight: bold;
    margin-top: 10px;
  }
</style>