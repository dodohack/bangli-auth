<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Return Jsonp or Ajax response based on input
     * @param Request $request
     * @param $json
     *
     * @return object - json encoded response with http 200 status
     */
    public function success(Request $request, $json) {
        /* Return JSONP or AJAX data */
        if ($request->has('callback'))
            return $request->input('callback') . '(' . $json . ')';
        else
            return $json;
    }
}
