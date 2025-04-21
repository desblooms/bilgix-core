<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get all expense categories
$categories = $db->select("SELECT c.*, 
                          (SELECT COUNT(*) FROM expenses WHERE categoryId = c.id) as expenseCount 
                          FROM expense_categories c 
                          ORDER BY c.name ASC");

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = !empty($_POST['categoryId']) ? intval($_POST['categoryId']) : null;
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Category name is required";
    
    // If no errors, insert/update database
    if (empty($errors)) {
        $data = [
            'name' => $name,
            'description' => $description
        ];
        
        if ($categoryId) {
            // Update existing category
            $updated = $db->update('expense_categories', $data, 'id = :id', ['id' => $categoryId]);
            
            if ($updated) {
                $_SESSION['message'] = "Category updated successfully!";
                $_SESSION['message_type'] = "success";
                redirect($basePath . 'modules/expenses/categories.php');
            } else {
                $errors[] = "Failed to update category. Please try again.";
            }
        } else {
            // Add new category
            $newCategoryId = $db->insert('expense_categories', $data);
            
            if ($newCategoryId) {
                $_SESSION['message'] = "Category added successfully!";
                $_SESSION['message_type'] = "success";
                redirect($basePath . 'modules/expenses/categories.php');
            } else {
                $errors[] = "Failed to add category. Please try again.";
            }
        }
    }
}

// Handle delete request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $deleteCategoryId = (int)$_GET['delete'];
    
    // Check if category is in use
    $categoryCount = $db->select("SELECT COUNT(*) as count FROM expenses WHERE categoryId = :id", 
                               ['id' => $deleteCategoryId]);
    
    if ($categoryCount[0]['count'] > 0) {
        $_SESSION['message'] = "Cannot delete category that is being used by expenses!";
        $_SESSION['message_type'] = "error";
        redirect($basePath . 'modules/expenses/categories.php');
    }
    
    // Delete the category
    $deleted = $db->delete('expense_categories', 'id = :id', ['id' => $deleteCategoryId]);
    
    if ($deleted) {
        $_SESSION['message'] = "Category deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete category!";
        $_SESSION['message_type'] = "error";
    }
    
    redirect($basePath . 'modules/expenses/categories.php');
}

// Handle edit request
$editCategory = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editCategoryId = (int)$_GET['edit'];
    $editResult = $db->select("SELECT * FROM expense_categories WHERE id = :id", ['id' => $editCategoryId]);
    
    if (!empty($editResult)) {
        $editCategory = $editResult[0];
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Expense Categories</h2>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <ul class="list-disc list-inside">
            <?php foreach($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Category Form -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-lg font-medium text-gray-800 mb-4">
            <?= $editCategory ? 'Edit Category' : 'Add New Category' ?>
        </h3>
        
        <form method="POST" class="space-y-4">
            <?php if ($editCategory): ?>
            <input type="hidden" name="categoryId" value="<?= $editCategory['id'] ?>">
            <?php endif; ?>
            
            <div>
                <label for="name" class="block text-gray-700 font-medium mb-2">Category Name *</label>
                <input type="text" id="name" name="name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $editCategory ? $editCategory['name'] : '' ?>">
            </div>
            
            <div>
                <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
                <textarea id="description" name="description" rows="2" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900"><?= $editCategory ? $editCategory['description'] : '' ?></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <?php if ($editCategory): ?>
                <a href="categories.php" class="py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                    Cancel
                </a>
                <?php endif; ?>
                
                <button type="submit" class="bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                    <i class="fas fa-save mr-2"></i> <?= $editCategory ? 'Update' : 'Add' ?> Category
                </button>
            </div>
        </form>
    </div>
    
    <!-- Categories List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Categories List</h3>
        </div>
        
        <?php if (count($categories) > 0): ?>
        <ul class="divide-y">
            <?php foreach($categories as $category): ?>
            <li class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= $category['name'] ?></p>
                        <?php if (!empty($category['description'])): ?>
                        <p class="text-sm text-gray-600"><?= $category['description'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="text-xs py-1 px-2 bg-gray-100 rounded-full text-gray-600">
                            <?= $category['expenseCount'] ?> expense<?= $category['expenseCount'] != 1 ? 's' : '' ?>
                        </span>
                        <a href="?edit=<?= $category['id'] ?>" class="text-slate-950">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($category['expenseCount'] == 0): ?>
                        <a href="#" class="text-red-600 delete-category" data-id="<?= $category['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <span class="text-gray-400 cursor-not-allowed">
                            <i class="fas fa-trash"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            No categories found
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t flex justify-between items-center p-2 bottom-nav">
    <a href="../../index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-home text-xl"></i>
        <span class="text-xs mt-1">Home</span>
    </a>
    <a href="../products/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-box text-xl"></i>
        <span class="text-xs mt-1">Products</span>
    </a>
    <a href="../sales/add.php" class="flex flex-col items-center p-2 text-gray-600">
        <div class="bg-red-900 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
            <i class="fas fa-plus text-xl"></i>
        </div>
        <span class="text-xs mt-1">New Sale</span>
    </a>
    <a href="../customers/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-users text-xl"></i>
        <span class="text-xs mt-1">Customers</span>
    </a>
    <a href="../reports/expenses.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<script>
    // Delete category confirmation
    const deleteButtons = document.querySelectorAll('.delete-category');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const categoryId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this category?')) {
                window.location.href = `?delete=${categoryId}`;
            }
        });
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>