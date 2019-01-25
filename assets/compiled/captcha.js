/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/assets/compiled/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/captcha.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/captcha.js":
/*!******************************!*\
  !*** ./assets/js/captcha.js ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("var $ = __webpack_require__(/*! jquery */ \"jquery\");\n\nvar grecaptcha = __webpack_require__(/*! grecaptcha */ \"grecaptcha\");\n\n$(document).ready(function () {\n  $('div#send-question button[type=\"submit\"]').click(function () {\n    var $form = $('div#send-question');\n    $.ajax({\n      url: 'api/send_contact_mail',\n      method: 'POST',\n      data: {\n        captcha: grecaptcha.getResponse(),\n        input_email: $('input[name=\"input_email\"]').val(),\n        input_message: $('textarea[name=\"input_message\"]').val()\n      },\n      success: function success(data) {\n        if (!data.captcha_success) {\n          $form.prepend('<div class=\"alert alert-warning\">Din CAPTCHA blev tyvärr inte godkänt. Testa igen!</div>');\n          grecaptcha.reset();\n          return;\n        } else if (!data.address_success) {\n          $form.prepend('<div class=\"alert alert-warning\">Du har glömt att ange en mejladress.</div>');\n          return;\n        } else if (!data.mail_success) {\n          $form.prepend('<div class=\"alert alert-danger\">Det blev nåt fel. Ditt meddelande har tyvärr inte kunnat skickas. Testa igen eller kontakta oss via kontaktsidan ovan.</div>');\n          return;\n        }\n\n        $form.html('<div class=\"alert alert-success\">Ditt meddelande har skickats!</div>');\n      }\n    });\n  });\n});\n\n//# sourceURL=webpack:///./assets/js/captcha.js?");

/***/ }),

/***/ "grecaptcha":
/*!*****************************!*\
  !*** external "grecaptcha" ***!
  \*****************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("module.exports = grecaptcha;\n\n//# sourceURL=webpack:///external_%22grecaptcha%22?");

/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("module.exports = jQuery;\n\n//# sourceURL=webpack:///external_%22jQuery%22?");

/***/ })

/******/ });