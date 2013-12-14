<?php
namespace infinite\web;

trait RenderTrait
{
	public function output() {
		echo $this->generate();
	}
}