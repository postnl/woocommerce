"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.isPromise = isPromise;

var _typeof2 = _interopRequireDefault(require("@babel/runtime/helpers/typeof"));

/**
 * Check if given value is a then-able.
 *
 * @param {*} obj - The value to test.
 * @return {boolean}
 */
function isPromise(obj) {
  return !!obj && ((0, _typeof2["default"])(obj) === 'object' || typeof obj === 'function') && typeof obj.then === 'function';
}