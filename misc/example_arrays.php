<?php
	// just some arrays to test functions
	$ex_a = ["hello", "what", [3 => "somewhat"]];
	$ex_b = [32, 54, 2, 19, 31, 44];
	$ex_c = ["32" => "camel", "31" => "penguin", "9" => "wolf"];
	$ex_d = ["ice", "forest"];
	$ex_e =  ["desert" => "camel", "ice" => "penguin", "forest" => "wolf"];
	$ex_f = [
	["id" => "1", "first_name" => "Adam", "last_name" => "Albrecht"],
	["id" => "2", "first_name" => "Beata", "last_name" => "Bensen"],
	["id" => "3", "first_name" => "Charlie", "last_name" => "Corelius"],
	["id" => "4", "first_name" => "David", "last_name" => "Didrichsen"],
	["id" => "5", "first_name" => "Emil", "last_name" => "Eriksson"],
	["id" => "6", "first_name" => "Fredrik", "last_name" => "Fortuna"],
	["id" => "7", "first_name" => "Georg", "last_name" => "Gerdtzen"],
	["id" => "8", "first_name" => "Hans", "last_name" => "Honniker"],
	["id" => "9", "first_name" => "Ida", "last_name" => "Ibsen"],
	["id" => "10", "first_name" => "Jens", "last_name" => "Jericho"]];


	$ex_g = ["" => "", "" => "", "" => "", "" => "", "" => "", "" => "", "" => "", "" => ""];
	$ex_h = ["" => ["", ""], "" => ["", ""], "" => ["", ""], "" => ["", ""], "" => ["", ""], "" => ["", ""]];
	$ex_i = 	[["id" => "1", "Parent" => "", "Name" => "Biggest 1", "Value" => "aa"],
	["id" => "2", "Parent" => "", "Name" => "Biggest 2", "Value" => "asd"],
	["id" => "3", "Parent" => "", "Name" => "Biggest 3", "Value" => "dee"],
	["id" => "4", "Parent" => "1", "Name" => "Big 4", "Value" => "e431"],
	["id" => "5", "Parent" => "1", "Name" => "Big 5", "Value" => "f795"],
	["id" => "6", "Parent" => "2", "Name" => "Big 6", "Value" => "g78944"],
	["id" => "7", "Parent" => "3", "Name" => "Big 7", "Value" => "h21356"],
	["id" => "8", "Parent" => "4", "Name" => "Small 8", "Value" => "j236374"],
	["id" => "9", "Parent" => "4", "Name" => "Small 9", "Value" => "u65897"],
	["id" => "10","Parent" => "9", "Name" => "Smallest 10", "Value" => "ze1345"]];
	$ex_j = [
	["type" => "a", "id" => "1", "first_name" => "Adam", "last_name" => "Albrecht"],
	["type" => "b", "id" => "2", "first_name" => "Beata", "last_name" => "Bensen"],
	["type" => "c", "id" => "3", "first_name" => "Charlie", "last_name" => "Corelius"],
	["type" => "a", "id" => "4", "first_name" => "David", "last_name" => "Didrichsen"],
	["type" => "a", "id" => "5", "first_name" => "Emil", "last_name" => "Eriksson"],
	["type" => "b", "id" => "6", "first_name" => "Fredrik", "last_name" => "Fortuna"],
	["type" => "c", "id" => "7", "first_name" => "Georg", "last_name" => "Gerdtzen"],
	["type" => "c", "id" => "8", "first_name" => "Hans", "last_name" => "Honniker"],
	["type" => "c", "id" => "9", "first_name" => "Ida", "last_name" => "Ibsen"],
    ["type" => "", "id" => "10", "first_name" => "Jens", "last_name" => "Jericho"],
	["type" => "d", "id" => "11", "first_name" => "Karl", "last_name" => "Kampfbert"]];
	$ex_k = [];
	$ex_l = [];
	$ex_m = [];
	$ex_n = [];
	$ex_o = [];
	$ex_p = [];
	$ex_q = [];
	$ex_r = [];
	$ex_s = [];
	$ex_t = [];
	$ex_u = [];
	$ex_v = [];
	$ex_w = [];
	$ex_x = [];
	$ex_y = [];
	$ex_z = [];
