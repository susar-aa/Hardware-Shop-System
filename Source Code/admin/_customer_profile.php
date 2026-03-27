<!-- Global Customer Profile Modal -->
<div id="global-customer-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-60 hidden transition-opacity duration-300 opacity-0">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 modal-content transform scale-95 opacity-0 transition-transform duration-300 max-h-[90vh] flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center rounded-t-2xl">
            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <div class="bg-blue-100 text-blue-600 rounded-full w-10 h-10 flex items-center justify-center">
                    <i class="fas fa-user-circle text-2xl"></i>
                </div>
                Customer Profile
            </h3>
            <button type="button" onclick="closeCustomerProfile()" class="text-gray-400 hover:text-red-500 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Scrollable Body -->
        <div class="flex-1 overflow-y-auto p-6 bg-gray-50/50">
            <div id="customer-profile-loader" class="text-center py-12">
                <i class="fas fa-circle-notch fa-spin fa-3x text-blue-500"></i>
                <p class="mt-4 text-gray-500 font-medium">Fetching details...</p>
            </div>

            <div id="customer-profile-content" class="hidden space-y-6">
                <!-- Basic Info Card -->
                <div class="bg-white p-5 shadow-sm rounded-xl border border-gray-100 border-l-4 border-l-blue-500">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs uppercase font-bold text-gray-400">Name</p>
                            <p class="text-lg font-bold text-gray-900" id="cp-name">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase font-bold text-gray-400">NIC / ID</p>
                            <p class="text-lg font-semibold text-gray-700" id="cp-nic">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase font-bold text-gray-400">Phone</p>
                            <p class="text-lg font-semibold text-gray-700" id="cp-phone">-</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase font-bold text-gray-400">Address</p>
                            <p class="text-sm font-medium text-gray-600 truncate" id="cp-address">-</p>
                        </div>
                    </div>
                </div>

                <!-- History Tabs Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Cheque History -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-80">
                        <div class="bg-gray-100 px-4 py-3 border-b flex justify-between items-center">
                            <h4 class="font-bold text-gray-700"><i class="fas fa-money-check text-orange-500 mr-2"></i>Recent Cheques</h4>
                        </div>
                        <div class="flex-1 overflow-y-auto p-0">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bank</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="cp-cheques-tbody" class="divide-y divide-gray-100">
                                    <!-- JS populated -->
                                </tbody>
                            </table>
                            <div id="cp-cheques-empty" class="hidden text-center py-8 text-gray-400">No cheque history.</div>
                        </div>
                    </div>

                    <!-- Credit History -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-80">
                        <div class="bg-gray-100 px-4 py-3 border-b flex justify-between items-center">
                            <h4 class="font-bold text-gray-700"><i class="fas fa-hand-holding-usd text-green-500 mr-2"></i>Recent Credit Sales</h4>
                        </div>
                        <div class="flex-1 overflow-y-auto p-0">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sale ID</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="cp-credits-tbody" class="divide-y divide-gray-100">
                                    <!-- JS populated -->
                                </tbody>
                            </table>
                            <div id="cp-credits-empty" class="hidden text-center py-8 text-gray-400">No credit history.</div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

    </div>
</div>

<script src="assets/js/customer_profile.js"></script>
