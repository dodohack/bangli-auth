<?php
/**
* This file defines all the status code returned by this auth server, error
* code is returned in 'status' payload in the json data.
* there are 3 part of code:
* 0 - Ok
* 1 - 99: Error code only returns to application server
* 100 - 500: Error code returns to client.
*
* E.g:
* ['status' => '1', 'msg' => 'Mismatched JWT_SECRET']
*/

return [

    /************************************************************************
     * 0
     * Ok status
     ************************************************************************/
    0 => ['status' => 0, 'msg' => 'OK'],

    /************************************************************************
     * 1 - 99
     * Error code for application server
     ************************************************************************/

    /* Application server sends mismatched JWT_SECRET to me. */
    1 => ['status' => 1, 'msg' => 'Mismatched JWT_SECRET'],

    /* Application server wants to create a user on me with duplicated
       name or email which already exist in my user table. */
    2 => ['status' => 2, 'msg' => 'Duplicated user name or email'],

    /* Try to store or update a database record, but fails for unknown reason */
    3 => ['status' => 3, 'msg' => 'Database error'],


    /************************************************************************
     * 100 - 500
     * Error code for user client
     ************************************************************************/
    101 => ['status' => 101, 'msg' => 'Invalid uuid'],
    /* JWT token invalid */
    102 => ['status' => 102, 'msg' => 'Token invalid'],
    /* JWT token expired */
    103 => ['status' => 103, 'msg' => 'Token expired'],
    /* Failed to update password */
    104 => ['status' => 104, 'msg' => 'Update password failed'],
    /* Invalid uuid */
    105 => ['status' => 105, 'msg' => 'Invalid UUID']
];