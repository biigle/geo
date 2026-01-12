<?php

namespace Biigle\Modules\Geo\Services\Support;

use Exception;
use PHPCoord\CoordinateOperation\GeographicValue;
use PHPCoord\Point\ProjectedPoint;
use PHPCoord\UnitOfMeasure\Length\Metre;
use PHPCoord\CoordinateReferenceSystem\Geographic2D;
use Biigle\Modules\Geo\Exceptions\TransformCoordsException;
use PHPCoord\CoordinateReferenceSystem\CoordinateReferenceSystem;

class Transformer {

    /**
     * Transform coordinates to epsg 4326
     *
     * @param $coords_current min and max coordinates of the geoTIFF
     * @param $pcs_code from the ProjectedCSTypeTag of the geoTIFF
     *
     * @throws TransformCoordsException if any exception occurs
     *
     * @return array in form [min_x, min_y, max_x, max_y]
     */
    public function transformToEPSG4326($coords_current, $pcs_code)
    {
        try {
            $crs = str_replace(':', '::', $pcs_code);
            $fromCRS = CoordinateReferenceSystem::fromSRID('urn:ogc:def:crs:' . $crs);
            $toCRS = Geographic2D::fromSRID(Geographic2D::EPSG_WGS_84);
            $transformed_coords = [];

            for ($i = 0; $i < count($coords_current); $i += 2) {
                $p = ProjectedPoint::create(
                    $fromCRS,
                    new Metre($coords_current[$i]),
                    new Metre($coords_current[$i + 1]),
                    null,
                    null
                );

                $gp = $p->asGeographicPoint();
                $gv = new GeographicValue($gp->getLatitude(), $gp->getLongitude(), null, $fromCRS->getDatum());
                // Throw an exception if the coordinates lie outside of the CRS bounding box
                if (!$fromCRS->getBoundingArea()->containsPoint($gv)) {
                    throw new Exception();
                }
                $to = $p->convert($toCRS);
                $transformed_coords = array_merge(
                    $transformed_coords,
                    [
                        $to->getLongitude()->getValue(),
                        $to->getLatitude()->getValue()
                    ]
                );
            }
            return $transformed_coords;
        } catch (Exception $e) {
            throw new TransformCoordsException();
        }
    }

}