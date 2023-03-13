let employeesApp = angular.module('employeesApp', ['listModule', 'apiModule', 'routingModule']);

employeesApp.factory('bridge', ControllerBridge);
employeesApp.directive('onEnter', onEnterDirective);
employeesApp.controller('EmployeeListController', ['$scope', 'api', 'listManager', 'routingModule', 'bridge', EmployeeListController]);

angular.bootstrap(document.getElementById("employeesAppHandler"),["employeesApp"]);
