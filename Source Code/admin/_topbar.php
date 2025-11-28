<!-- Top Bar -->
<header class="bg-white shadow-md p-4 flex justify-between items-center">
    <button id="menu-btn" class="md:hidden text-gray-700"><i class="fas fa-bars text-xl"></i></button>
    
    <div class="flex items-center ml-auto">
        <!-- UPDATED: User and Branch Info Display -->
        <span class="mr-4 text-gray-700 text-sm md:text-base">
            Welcome, <strong id="user-name">User</strong>! 
            <span id="user-branch-display" class="text-gray-500 text-xs"></span>
        </span>
        <button id="logout-button" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
            Logout
        </button>
    </div>
</header>