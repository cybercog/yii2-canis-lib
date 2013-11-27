<?php
namespace infinite\web;

trait RenderTrait
{
	public function render() {
		echo $this->generate();
	}
}