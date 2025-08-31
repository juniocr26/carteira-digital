<?php

namespace App\DTO\Base;

class DTOBase
{
    /**
     * Retorna a representação em array dos atributos da entidade.
     *
     * @return array
     */
    public function toArray(): array
    {

        $array = [];

        foreach ($this as $key => $value) {
            // Verifica se o valor é uma instância de uma enumeração
            if ($value instanceof \UnitEnum) {
                $array[$key] = $value->value;
                continue;
            }
            
            // Verifica se o valor é uma instância de um DTO para fazer o mapeamento para array dentro dele tambem
            if ($value instanceof DTOBase) {
                $array[$key] = $value->toArray();
                continue;
            }

            // Verifica se o valor é um array para fazer o mapeamento dos objetos dentro dele para array
            if (is_array($value)) {
                $subArray = [];
                foreach ($value as $valueItem) {
                    if ($valueItem instanceof DTOBase) {
                        array_push($subArray, $valueItem->toArray());
                        continue;
                    }
                    array_push($subArray, $valueItem);
                }
                $array[$key] = $subArray;
                continue;
            }
            
            $array[$key] = $value;
        }
    
        return $array;
    }
}