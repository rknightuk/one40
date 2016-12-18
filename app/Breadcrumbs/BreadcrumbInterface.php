<?php namespace App\Breadcrumbs;

interface BreadcrumbInterface {

	public function setListElement($element);

	public function setCssClasses($class);

	public function setDivider($divider);

	public function addCrumb($text, $link);

}