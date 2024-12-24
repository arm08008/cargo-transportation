<?php

namespace App\Services;

class CargoService
{
    public const MAX_PLACE = ['length' => 1200, 'width' => 230, 'height' => 230, 'max_weight' => 22000];

    public const BIG_CONTAINER = 'big';
    public const SMALL_CONTAINER = 'small';

    public string $transportType;
    public int $bigContainersCount = 0;
    public int $smallContainersCount = 0;
    public int $remainingBottomSpace = 0;


    public function calculate(array $data) :array
    {
        if(!count($data['cargo'])) {
            throw new \Exception('Cargo data is empty');
        }

        $this->transportType = $data['transport_type'];

        $noStackingCargosFullSize = 0;
        $bottomCargosFullSize = 0;
        $topCargosFullVolume = 0;
        $bottomCargosFullVolume = 0;
        $anyCargosFullVolume = 0;
        $anyCargosFullSize = 0;

        foreach ($data['cargo'] as $cargo) {
            if (!$this->checkCargoMaxPlace($cargo)) {
                continue;
            }
            if ($cargo['stacking'] == "only_bottom") {
                $cargo['size'] =  $cargo['length'] * $cargo['width'];
                $bottomCargosFullSize += $cargo['length'] * $cargo['width'] * $cargo['quantity'];
                $bottomCargosFullVolume += $cargo['length'] * $cargo['width'] * $cargo['height'] * $cargo['quantity'];
            }
            elseif ($cargo['stacking'] == "no_stacking") {
                $noStackingCargosFullSize += $cargo['length'] * $cargo['width'] * $cargo['quantity'];
            } elseif ($cargo['stacking'] == "only_top") {
                $cargo['size'] =  $cargo['length'] * $cargo['width'];
                $cargo['volume'] = $cargo['length'] * $cargo['width'] * $cargo['height'];
                $topCargosFullVolume += $cargo['length'] * $cargo['width'] * $cargo['height'] * $cargo['quantity'];
            } else {
                $anyCargosFullVolume += $cargo['length'] * $cargo['width'] * $cargo['height'] * $cargo['quantity'];
                $anyCargosFullSize += $cargo['length'] * $cargo['width'] * $cargo['quantity'];
            }
        }

        $this->remainingBottomSpace = 0;
        $this->calculateContainers($noStackingCargosFullSize + $bottomCargosFullSize);
        $fullBigVolume = 0;
        $fullSmallVolume = 0;
        if ($this->bigContainersCount) {
            $bigContainer = $this->getContainer(self::BIG_CONTAINER);
            $fullBigVolume = $bigContainer['length'] * $bigContainer['width'] * $bigContainer['height'] * $this->bigContainersCount;
        }
        if ($this->smallContainersCount) {
            $smallContainer = $this->getContainer(self::SMALL_CONTAINER);
            $fullSmallVolume = $smallContainer['length'] * $smallContainer['width'] * $smallContainer['height'] * $this->smallContainersCount;
        }
        $freeContainersVolume = $fullBigVolume + $fullSmallVolume - $bottomCargosFullVolume;

        if ($freeContainersVolume > $topCargosFullVolume) {
            $remainingVolume = $freeContainersVolume - $topCargosFullVolume;

            //Put some any stacking cargos to the second tier as there is a free space
            if ($anyCargosFullVolume > $remainingVolume) {
                $anyCargosFullVolume = $anyCargosFullVolume - $remainingVolume;
                $anyCargosSize = $anyCargosFullVolume / 100; //Need to discuss
                $this->calculateContainers($anyCargosSize);
            }
        }
        //Put any stacking cargos to the first tier
        if ($freeContainersVolume < $bottomCargosFullVolume) {
            $this->calculateContainers($anyCargosFullSize);
        }

        return [
            self::SMALL_CONTAINER => $this->smallContainersCount,
            self::BIG_CONTAINER => $this->bigContainersCount,
        ];
    }

    private function checkCargoMaxPlace(array $cargo) :bool
    {
        return !(
            $cargo['length'] > self::MAX_PLACE['length'] ||
            $cargo['width'] > self::MAX_PLACE['width'] ||
            $cargo['height'] > self::MAX_PLACE['height'] ||
            $cargo['weight'] > self::MAX_PLACE['max_weight']
        );
    }

    private function calculateContainers(int $cargosFullSize) :void
    {
        if ($this->remainingBottomSpace > 0) {
            if ($cargosFullSize < $this->remainingBottomSpace) {
                $this->remainingBottomSpace = $this->remainingBottomSpace - $cargosFullSize;
            } elseif ($cargosFullSize == $this->remainingBottomSpace) {
                $this->remainingBottomSpace = 0;
            } else {
                $cargosFullSize = $cargosFullSize + $this->remainingBottomSpace;
                $this->calculateCargosArea($cargosFullSize);
            }
        } else{
            $this->calculateCargosArea($cargosFullSize);
        }
    }

    private function calculateSmallContainers(int $cargosFullSize) :void
    {
        $smallContainer = $this->getContainer(self::SMALL_CONTAINER);
        $containerSize = $smallContainer['length'] * $smallContainer['width'];

        if ($cargosFullSize <= $containerSize) {
            $this->smallContainersCount += 1;
            $this->remainingBottomSpace =  $containerSize - $cargosFullSize;
        } else {
            $countContainers = ceil($cargosFullSize / $containerSize);
            $fullSize = $countContainers * $containerSize;
            $this->remainingBottomSpace = $fullSize - $cargosFullSize;
            $this->smallContainersCount += $countContainers;
        }
    }

    private function calculateCargosArea(int $cargosFullSize) :void
    {
        $bigContainer = $this->getContainer(self::BIG_CONTAINER);
        $bigContainerSize = $bigContainer['length'] * $bigContainer['width'];

        if ($cargosFullSize > $bigContainerSize) {
            $countBigContainers = floor($cargosFullSize / $bigContainerSize);
            $allBigContainersSize = $countBigContainers * $bigContainerSize;
            $remainingCargosSize = $cargosFullSize - $allBigContainersSize;
            $this->bigContainersCount += $countBigContainers;
            $this->calculateSmallContainers($remainingCargosSize);
        } elseif ($cargosFullSize == $bigContainerSize) {
            $this->bigContainersCount += 1;
            $this->remainingBottomSpace = 0;
        }
        else {
            $this->calculateSmallContainers($cargosFullSize);
        }
    }


    private function getContainer(string $size)
    {
        return config('cargo.containers.' . $this->transportType . '.' . $size);
    }

    public function calculateForTrucks(int $fullArea, $containerSize)
    {
        $freeSpace = $this->remainingBottomSpace - $fullArea;
        $fullArea = $freeSpace + $containerSize;
        if ($fullArea - $cargoSize >= 0) {
            $this->smallContainersCount += 1;
        } elseif($fullArea - $cargoSize < 0)
        {
            $this->smallContainersCount += 1;
        } else {


        }
    }
}
