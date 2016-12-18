<?php namespace App\Breadcrumbs;

use Creitive\Breadcrumbs\Facades\Breadcrumbs;

class CreitiveBreadcrumb implements BreadcrumbInterface {

	public function setListElement($element)
	{
		Breadcrumbs::setListElement($element);
	}

	public function setCssClasses($class)
	{
		Breadcrumbs::setCssClasses($class);
	}

	public function setDivider($divider)
	{
		Breadcrumbs::setDivider($divider);
	}

	public function addCrumb($text, $link)
	{
		Breadcrumbs::addCrumb($text, $link);
	}

}