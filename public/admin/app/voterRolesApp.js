let voterRolesApp = angular.module('voterRolesApp', ['listModule', 'apiModule', 'routingModule', 'swangular']);

voterRolesApp.factory('bridge', ControllerBridge);
voterRolesApp.directive('onEnter', onEnterDirective);
voterRolesApp.controller('VoterRoleListController', ['$scope', 'api', 'listManager', 'routingModule', 'swangular', 'bridge', VoterRoleListController]);
voterRolesApp.controller('NewVoterRoleController', ['$scope', 'api', 'swangular', 'bridge', NewVoterRoleController]);

angular.bootstrap(document.getElementById("voterRolesAppHandler"),["voterRolesApp"]);