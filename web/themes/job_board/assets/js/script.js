(function($, Drupal) {

	Drupal.JobBoard = Drupal.JobBoard || {};

	Drupal.behaviors.ActionsJobBoard = {
		attach: function(context, settings) {
			Drupal.JobBoard.initPage();
			Drupal.JobBoard.slideJobBoardHeadhunters();
			Drupal.JobBoard.searchJobHomePage();
			Drupal.JobBoard.addEmailHeadhunters();
			Drupal.JobBoard.setColorViewProduct();
			Drupal.JobBoard.applyJob();
			Drupal.JobBoard.hideLoginBlogPage();
			Drupal.JobBoard.fieldConditions();
			Drupal.JobBoard.addcss();
		}
	};

	Drupal.JobBoard.addcss = function() {
		$('#owl-carousel').owlCarousel({
                        rtl:true,
			items: 1,
			loop: true,
			nav: false,
			autoplay: true,
			margin: 10,
			autoHeight: false,
			autoHeightClass: 'owl-height',
			responsive:{
				0:{
					items:1,
					nav:false
				}
			}
		});
		$("div.bhoechie-tab-menu>div.list-group>a").click(function(e) {
			e.preventDefault();
			$(this).siblings('a.active').removeClass("active");
			$(this).addClass("active");
			var index = $(this).index();
			$("div.bhoechie-tab>div.bhoechie-tab-content").removeClass("active");
			$("div.bhoechie-tab>div.bhoechie-tab-content").eq(index).addClass("active");
		});

		$( "#candidat-subscribe .card-header" ).remove();
		$( "#entreprise-subscribe .card-header" ).remove();

		$( "#candidat-subscribe" ).prepend('<div class="card-header"><legend><span class="fieldset-legend">Candidat</span></legend></div>');
		$( "#entreprise-subscribe" ).prepend('<div class="card-header"><legend><span class="fieldset-legend">Entreprise</span></legend></div>');
	}
	Drupal.JobBoard.fieldConditions = function() {
		var doConditions = function (fieldPays, fieldVille, fieldVilleTn) {
			if (fieldPays.val() === '_none') {
				fieldVille.hide();
				fieldVilleTn.hide();
			}
			fieldPays.change(function() {
				switch (fieldPays.val()) {
					case "_none":
						fieldVille.hide();
						fieldVilleTn.hide();
						break;

					case "1382":
						fieldVille.hide();
						fieldVilleTn.show();
						break;

					default:
						fieldVille.show();
						fieldVilleTn.hide();
				}
			});
		}
		// Profile candidat
		if ($("#edit-entity-candidat-field-pays").length !== 0) {
			var fieldPays = $("#edit-entity-candidat-field-pays");
			var fieldVille = $(".field--name-field-ville");
			var fieldVilleTn = $(".field--name-field-ville-tunisie");
      doConditions(fieldPays, fieldVille, fieldVilleTn);
		}

		// Profile entreprise
		if ($("#edit-entity-entreprise-field-pays").length !== 0) {
			var fieldPays = $("#edit-entity-entreprise-field-pays");
			var fieldVille = $(".field--name-field-ville");
			var fieldVilleTn = $(".field--name-field-ville-tunisie");
			doConditions(fieldPays, fieldVille, fieldVilleTn);
		}

		// offre

		if ($("#edit-field-pays").length !== 0) {
			var fieldPays = $("#edit-field-pays");
			var fieldVille = $(".field--name-field-ville");
			var fieldVilleTn = $(".field--name-field-ville-tunisie");
			doConditions(fieldPays, fieldVille, fieldVilleTn);
		}


		//console.log("ddazdazdaz");
	};

	Drupal.JobBoard.hideLoginBlogPage = function() {
		$('.node--type-blog .node__cemment #comment-form #edit-actions #edit-submit').val('Comment');
		if ($('body').hasClass('user-logged-in')) {
			$('.login-register-comment').addClass('hidden');
		}
	};

	Drupal.JobBoard.applyJob = function() {
		$('.apply-this-job').on("click", function() {
			if (!$(this).hasClass('active')) {
				var temp = $(this).attr('href');
				var url = temp + '?';
				var applicationEmail = $('.views-field-email .application-email').text();
				url += 'email=' + applicationEmail;
				var conpanyName = $('.block-top-job-detail .view-job .views-field-company-name').text();
				url += '&conpany=' + conpanyName;
				var jobName = $('.page-title .field--name-title').text();
				url += '&jobname=' + jobName;
				$(this).attr("href", url);
				$(this).addClass('active');
			}
		});
		$('#edit-job-name').val(getParameterByName('jobname'));
		$('#edit-company').val(getParameterByName('conpany'));
		$('#edit-application-email').val(getParameterByName('email'));
	}

	Drupal.JobBoard.setColorViewProduct = function() {
		$("#node-job-form #edit-actions #edit-submit").val("Preview your listing");
		$("#node-job-form #edit-actions #edit-preview").addClass('hidden');
		$(".view-product .view-content .views-table .form-submit").val("Post Now");
	}

	Drupal.JobBoard.addEmailHeadhunters = function() {
		var email = getParameterByName('email');
		$('#edit-email-headhunters').val(email);
		$('#edit-email-headhunters').attr("disabled","disabled");
		$('.edit-email-headhunter').on("click", function() {
			$('#edit-email-headhunters').removeAttr("disabled");
			$(this).addClass('hidden');
		});
	}

	function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
	}

	Drupal.JobBoard.searchJobHomePage = function() {
		$('#search-jobs .show-more').on("click", function() {
			$(this).addClass('hidden');
			$('#search-jobs .select-job-level').removeClass('hidden');
			$('#search-jobs .select-job-type').removeClass('hidden');
			$('#search-jobs .show-less').removeClass('hidden');
		});
		$('#search-jobs .show-less').on("click", function() {
			$(this).addClass('hidden');
			$('#search-jobs .select-job-level').addClass('hidden');
			$('#search-jobs .select-job-type').addClass('hidden');
			$('#search-jobs .show-more').removeClass('hidden');
		});
		var countJob = $('.count-jobs-available').text();
		$('#search-jobs .jobs-available').html('<span>' + countJob + ' jobs</span> available');
		// Seach job
		$('#search-jobs #find-job').on("click", function() {
			var temp = $('#search-jobs #find-job').attr('href');
			var url = temp + '?';
			var title = $('#search-jobs-title').val();
			if (title != '') {
				url += 'title=' + title;
			}
			var jobType = $('.select-job-type').val();
			if (jobType != 'default') {
				url += '&field_employment_type_target_id%5B' + jobType + '%5D=' + jobType;
			}
			var jobLevel = $('.select-job-level').val();
			if (jobLevel != 'default') {
				url += '&field_job_level_target_id%5B' + jobLevel + '%5D=' + jobLevel;
			}
			var jobLocation = $('#search-jobs-address').val();
			var jobLocationId = '';
			$('.view-term-job-location .view-content .views-row').each(function(i) {
				var title = $(this).find('.title-select-option').text();
				if (title == jobLocation) {
					jobLocationId = $(this).find('.value-select-option').text();
				}
			});
			if (jobLocationId != '') {
				url += '&field_location_target_id%5B' + jobLocationId + '%5D=' + jobLocationId;
			}
			$(this).attr("href", url);
		});
		// add value to select option
		var arrJobLevel = [];
		var arrJobType = [];
		var arrJobLocation = [];
		$('.view-term-job-level .view-content .views-row').each(function(i) {
			var title = $(this).find('.title-select-option').text();
			var value = $(this).find('.value-select-option').text();
			var temp = '<option value="' + value+ '">' + title + '</option>';
			arrJobLevel[i] = temp;
		});
		$('.view-term-job-type .view-content .views-row').each(function(i) {
			var title = $(this).find('.title-select-option').text();
			var value = $(this).find('.value-select-option').text();
			var temp = '<option value="' + value+ '">' + title + '</option>';
			arrJobType[i] = temp;
		});
		if (!$('#search-jobs').hasClass('add-select-option')) {
			for (var i = 0; i < arrJobLevel.length; i++) {
				$('#search-jobs .select-job-level').append(arrJobLevel[i]);
			}
			for (var i = 0; i < arrJobType.length; i++) {
				$('#search-jobs .select-job-type').append(arrJobType[i]);
			}	
			$('#search-jobs').addClass('add-select-option');
		}
		$('.view-term-job-location .view-content .views-row').each(function(i) {
			var title = $(this).find('.title-select-option').text();
			arrJobLocation[i] = title;
		});

    $("#search-jobs-address").autocomplete({
      source: arrJobLocation
    });
	};

	Drupal.JobBoard.slideJobBoardHeadhunters = function() {
		$('.job-board-headhunters').addClass('swiper-container');
		$('.job-board-headhunters .view-content').addClass('swiper-wrapper');
		$('.job-board-headhunters').append('<div class="swiper-button-next"></div>');
		$('.job-board-headhunters').append('<div class="swiper-button-prev"></div>');
		$('.job-board-headhunters .view-content .views-row').each(function() {
			$(this).addClass('swiper-slide');
		});
		var swiper = new Swiper('.swiper-container', {
      slidesPerView: 2,
      slidesPerColumn: 2,
      spaceBetween: 30,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
    });
	};

	Drupal.JobBoard.initPage = function() {
		if (!$('.user-new-password').hasClass('added')) {
            $('.menu-account-yet .register-as-applicant').attr("href",drupalSettings.path.baseUrl +'user/create/applicant');
            $('.menu-account-yet .register-as-recruiter').attr("href",drupalSettings.path.baseUrl +'user/create/recruiter');
			$('#user-login-form #edit-actions').before('<a class="user-new-password" href="'+ drupalSettings.path.baseUrl +'user/password">'+Drupal.t('Request new password')+'</a>');
			var pathnameUrl = window.location.pathname;
			if (pathnameUrl.search('/ar') != -1) {
				$('.menu-account-yet .register-as-applicant').attr("href",drupalSettings.path.baseUrl +'ar/user/create/applicant');
				$('.menu-account-yet .register-as-recruiter').attr("href",drupalSettings.path.baseUrl +'ar/user/create/recruiter');
				$('#user-login-form .user-new-password').attr("href",drupalSettings.path.baseUrl +'ar/user/password');
			}
			$('.user-new-password').addClass('added');
		}		
		$('.node-job_preferences #edit-submit').val('Save');
		$('.node-resumes #edit-submit').val('Save');
		$('.node-education #edit-submit').val('Save');
		if (!$('.title.comment-form__title').hasClass('added')) {
			$('.title.comment-form__title').text('Leave a comment');
			$('<p>Make sure you enter the (*) required information where indicated. HTML code is not allowed.</p>').insertAfter('.title.comment-form__title');
			$('.title.comment-form__title').addClass('added');
		}
		$('.region-sidebar-first .block-views-exposed-filter-blockjob-page-job .form--inline #edit-title').attr("placeholder", "Type here to search");
	};
})(jQuery, Drupal);