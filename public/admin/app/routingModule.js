let routingModule = angular.module('routingModule', []);

routingModule.factory('routingModule', ['$window', function($window) {
    return {
        openDashboard: function(){
            $window.location.href = Routing.generate('admin_dashboard', { }, true);
        },
        openTrade: function(tradeId){
            $window.location.href = Routing.generate('admin_trade', { 'tradeId': tradeId }, true);
        },
        openOrder: function(orderId){
            $window.location.href = Routing.generate('admin_order', { 'orderId': orderId }, true);
        },
        openWorkspace: function(workspaceId){
            $window.location.href = Routing.generate('admin_workspace', { 'workspaceId': workspaceId }, true);
        },
        openEmployee: function(employeeId){
            $window.location.href = Routing.generate('admin_employee', { 'employeeId': employeeId }, true);
        },
        openPOSOrder: function(POSOrderId){
            $window.location.href = Routing.generate('admin_pos_order', { 'POSOrderId': POSOrderId }, true);
        },
        openWallet: function(walletId){
            $window.location.href = Routing.generate('admin_wallet', { 'walletId': walletId }, true);
        },
        openUser: function(userId){
            $window.location.href = Routing.generate('admin_user', { 'userId': userId }, true);
        },
        openWithdrawal: function(withdrawalId){
            $window.location.href = Routing.generate('admin_withdrawal', { 'withdrawalId': withdrawalId }, true);
        },
        openInternalTransfer: function(internalTransferId){
            $window.location.href = Routing.generate('admin_internal_transfer', { 'internalTransferId': internalTransferId }, true);
        },
        openCheckoutOrder: function(checkoutOrderId){
            $window.location.href = Routing.generate('admin_checkout_order', { 'checkoutOrderId': checkoutOrderId }, true);
        },
        openDeposit: function(depositId){
            $window.location.href = Routing.generate('admin_deposit', { 'depositId': depositId }, true);
        },
    }
}]);
