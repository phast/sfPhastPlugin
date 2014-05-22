<?php


class PhastPageQuery extends PageQuery
{

    public function forProd(){
        $this->filterByVisible(1);
        $this->orderByPosition();
        return $this;
    }

    public function forRouting($keys){
        $this->filterByUri($keys);
        $this->filterByVisible(1);
        return $this;
    }

    public function forRetrieveByRoute($uri, $pattern = null, $requirements = null){
        $this
            ->filterByUri($uri)
            ->filterByRoutePattern($pattern)
            ->filterByRouteRequirements($requirements);

        return $this;
    }

}
