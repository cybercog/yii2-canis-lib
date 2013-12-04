$preparer.add(function(context) {
	$("input[data-smart],select[data-smart],button[data-smart]").each(function() {
		var $this = $(this);
		var tagName = $this.prop('tagName').toLowerCase();
		var defaultSmartData = {
			'fallbackType': {'tag': 'input', 'type': 'text'},
			'blank': false
		};
		var smartData = jQuery.extend(true, defaultSmartData, $this.data('smart'));
		$this.removeAttr('data-smart');
		$this.addClass('smart');

		if (smartData.watchField === undefined) {
			$.debug("Smart watch called without watchField");
			return;
		}

		$this.alternativeField = $({});
		if (smartData.fallbackType !== undefined) {
			var altTag = smartData.fallbackType.tag;
			delete smartData.fallbackType.tag;
			smartData.fallbackType.id = $this.attr("id") + "_alt";
			smartData.fallbackType.name = $this.attr("name");
			smartData.fallbackType.value = $this.attr("value");
			$this.alternativeField = $("<"+ altTag +"/>", smartData.fallbackType).hide();
			$this.alternativeField.addClass($this.attr('class'));
			$this.alternativeField.appendTo($this.parent());
		}

		$this.activateAlternative = function() {
			$this.hide();
			$this.alternativeField.show();
		};

		$this.deactivateAlternative = function() {
			$this.alternativeField.hide();
			$this.show();
		};
		
		$this.bind('refresh', function() {
			var watchValue = $(smartData.watchField).val();
			if (tagName === 'select') {
				if (smartData.options === undefined || smartData.options[watchValue] === undefined) {
					$this.activateAlternative();
					$this.alternativeField.val($this.val());
				} else {
					$this.deactivateAlternative();
					$this.renderSelect(smartData.options[watchValue], smartData.blank);
					$this.val($this.alternativeField.val());
				}
			}
		});

		$(smartData.watchField).bind('change', function() {
			$this.trigger('refresh');
		});
		$this.trigger('refresh');
	});
});