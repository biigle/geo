/**
 * @namespace biigle.transects
 * @ngdoc controller
 * @name GeoFilterController
 * @memberOf biigle.transects
 * @description Manages the annotation filter feature
 */
angular.module('biigle.transects').controller('GeoFilterController', function ($scope, filter, TRANSECT_ID, $q, images) {
        "use strict";
        var key = 'biigle.geo.imageSequence.' + TRANSECT_ID;
        var seqence = [];

        var refreshSequence = function () {
            var newSequence = JSON.parse(localStorage.getItem(key)) || [];

            if (!angular.equals(seqence, newSequence)) {
                seqence.length = 0;
                Array.prototype.push.apply(seqence, newSequence);
                filter.refresh();
                images.updateFiltering();
            }
        };

        filter.add({
            name: 'geo selection',
            helpText: 'All images that were selected on the world map.',
            helpTextNegate: 'All images that were not selected on the world map.',
            template: 'geoFilterRule.html',
            refreshSequence: true,
            getSequence: function () {
                var deferred = $q.defer();
                seqence.$promise = deferred.promise;
                deferred.resolve(seqence);

                return seqence;
            }
        });

        window.addEventListener('storage', function () {
            $scope.$apply(refreshSequence);
        });
        refreshSequence();
    }
);
