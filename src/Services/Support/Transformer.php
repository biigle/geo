<?php

namespace Biigle\Modules\Geo\Services\Support;

use Exception;
use PHPCoord\Point\ProjectedPoint;
use PHPCoord\UnitOfMeasure\Length\Metre;
use PHPCoord\CoordinateReferenceSystem\Projected;
use PHPCoord\CoordinateReferenceSystem\Geographic2D;
use Biigle\Modules\Geo\Exceptions\TransformCoordsException;
use PHPCoord\CoordinateReferenceSystem\CoordinateReferenceSystem;

class Transformer
{

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
        if (str_ends_with($pcs_code, ":4326")) {
            return $coords_current;
        }

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

                $to = $p->convert($toCRS);
                $transformed_coords = array_merge(
                    $transformed_coords,
                    [
                        $to->getLongitude()->getValue(),
                        $to->getLatitude()->getValue()
                    ]
                );
            }
        } catch (Exception $e) {
            throw new TransformCoordsException();
        }
        return $transformed_coords;
    }

    /**
     * Check if the CRS is projected
     *
     * @param mixed $code EPSG code
     *
     * @return bool true if the CRS is projected, otherwise false
     */
    public function isProjected($code)
    {
        try {
            $code = str_replace(':', '::', $code);
            Projected::fromSRID("urn:ogc:def:crs:" . $code);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Fix corner sorting from UR -> LL to LL -> UR
     *
     * @param mixed $coords
     *
     * @return array
     */
    public function maybeFixCoords($coords)
    {
        // Handle coordinates at wrap point
        // Transform edges:
        //        _               _
        //  from   | + |_ to |_ +  |
        if ($coords[0] > $coords[2]) {
            $coords[2] += 360;
        }

        return $coords;
    }

}
