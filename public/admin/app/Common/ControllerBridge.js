function ControllerBridge($rootScope) {
    return {
        depositApproved: function(){
            $rootScope.$broadcast('depositApproved');
        },
        refreshTransfers: function(){
            $rootScope.$broadcast('refreshTransfers');
        },
        refreshWalletBalances: function(){
            $rootScope.$broadcast('refreshWalletBalances');
        },
        refreshWalletInfo: function(){
            $rootScope.$broadcast('refreshWalletInfo');
        },
        refreshDepositInfo: function(){
            $rootScope.$broadcast('refreshDepositInfo');
        },
        refreshWithdrawalInfo: function(){
            $rootScope.$broadcast('refreshWithdrawalInfo');
        },
        refreshInternalTransferInfo: function(){
            $rootScope.$broadcast('refreshInternalTransferInfo');
        },
        refreshUserInfo: function(){
            $rootScope.$broadcast('refreshUserInfo');
        },
        refreshOrderInfo: function(){
            $rootScope.$broadcast('refreshOrderInfo');
        },
        refreshCheckoutOrderInfo: function(){
            $rootScope.$broadcast('refreshCheckoutOrderInfo');
        },
        refreshWorkspaceInfo: function(){
            $rootScope.$broadcast('refreshWorkspaceInfo');
        },
        refreshEmployeeInfo: function(){
            $rootScope.$broadcast('refreshEmployeeInfo');
        },
        refreshPOSOrderInfo: function(){
            $rootScope.$broadcast('refreshPOSOrderInfo');
        },
        refreshDepositRequests: function(){
            $rootScope.$broadcast('refreshDepositRequests');
        },
        refreshVoterRoles: function(){
            $rootScope.$broadcast('refreshVoterRoles');
        },
        refreshSystemTags: function(){
            $rootScope.$broadcast('refreshSystemTags');
        },
        initLoadedWallet: function(walletId){
            $rootScope.$broadcast('loadedWallet', { 'walletId': walletId});
        },
        initLoadedDeposit: function(depositId){
            $rootScope.$broadcast('loadedDeposit', { 'depositId': depositId});
        },
        initLoadedWithdrawal: function(withdrawalId){
            $rootScope.$broadcast('loadedWithdrawal', { 'withdrawalId': withdrawalId});
        },
        initLoadedInternalTransfer: function(internalTransferId){
            $rootScope.$broadcast('loadedInternalTransfer', { 'internalTransferId': internalTransferId});
        },
        initLoadedUser: function(userId){
            $rootScope.$broadcast('loadedUser', { 'userId': userId});
        },
        initLoadedOrder: function(orderId){
            $rootScope.$broadcast('loadedOrder', { 'orderId': orderId});
        },
        initLoadedCheckoutOrder: function(checkoutOrderId){
            $rootScope.$broadcast('loadedCheckoutOrder', { 'checkoutOrderId': checkoutOrderId});
        },
        initLoadedWorkspace: function(workspaceId){
            $rootScope.$broadcast('loadedWorkspace', { 'workspaceId': workspaceId});
        },
        initLoadedEmployee: function(employeeId){
            $rootScope.$broadcast('loadedEmployee', { 'employeeId': employeeId});
        },
        initLoadedPOSOrder: function(POSOrderId){
            $rootScope.$broadcast('loadedPOSOrder', { 'POSOrderId': POSOrderId});
        },
    };
};
