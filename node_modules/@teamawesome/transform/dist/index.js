"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _regenerator = _interopRequireDefault(require("@babel/runtime/regenerator"));

var _slicedToArray2 = _interopRequireDefault(require("@babel/runtime/helpers/slicedToArray"));

var _typeof2 = _interopRequireDefault(require("@babel/runtime/helpers/typeof"));

var _toConsumableArray2 = _interopRequireDefault(require("@babel/runtime/helpers/toConsumableArray"));

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _get2 = _interopRequireDefault(require("@babel/runtime/helpers/get"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

var _wrapNativeSuper2 = _interopRequireDefault(require("@babel/runtime/helpers/wrapNativeSuper"));

var _defineProperty2 = _interopRequireDefault(require("@babel/runtime/helpers/defineProperty"));

var _util = require("./util");

var _Symbol$iterator;

_Symbol$iterator = Symbol.iterator;

var Pipe =
/*#__PURE__*/
function (_Map) {
  (0, _inherits2["default"])(Pipe, _Map);

  /**
   * @type {*[]}
   */

  /**
   * @param {Function|Iterable|Object} wrapped
   * @return {Function}
   */
  function Pipe() {
    var _this;

    var wrapped = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    (0, _classCallCheck2["default"])(this, Pipe);
    _this = (0, _possibleConstructorReturn2["default"])(this, (0, _getPrototypeOf2["default"])(Pipe).call(this));
    (0, _defineProperty2["default"])((0, _assertThisInitialized2["default"])(_this), "order", []);
    var entries;

    if (wrapped[Symbol.iterator]) {
      entries = (0, _toConsumableArray2["default"])(wrapped.entries());
    } else if ((0, _typeof2["default"])(wrapped) === 'object') {
      entries = Object.entries(wrapped);
    } else if (typeof wrapped === 'function') {
      entries = [['main', wrapped]];
    }

    entries.forEach(function (_ref) {
      var _ref2 = (0, _slicedToArray2["default"])(_ref, 2),
          key = _ref2[0],
          value = _ref2[1];

      return _this.set(key, value);
    });
    return _this;
  }
  /**
   * @param args
   * @param thisArg
   */


  (0, _createClass2["default"])(Pipe, [{
    key: "call",
    value: function call(args, thisArg) {
      var _this2 = this;

      return this.order.reduce(function (value, action) {
        var func = _this2.get(action);

        if ((0, _util.isPromise)(value)) {
          return Promise.resolve(value).then(function (resolvedValue) {
            return func.call(thisArg, resolvedValue);
          });
        }

        return func.call(thisArg, value);
      }, args);
    }
    /**
     * Push a function on the stack
     *
     * @param {string|*} key The name of the hook
     * @param {Function} value The function to call
     * @return {Pipe}
     */

  }, {
    key: "set",
    value: function set(key, value) {
      if (!this.has(key)) {
        this.order.push(key);
      }

      return (0, _get2["default"])((0, _getPrototypeOf2["default"])(Pipe.prototype), "set", this).call(this, key, value);
    }
    /**
     * Insert a function in the stack
     *
     * @param {string|*} key The name of the hook
     * @param {Function} value The function to call
     * @param {string|*} neighbour The neighbour to insert before or after
     * @param {boolean} after True to insert after the neighbour, false to
     *                        insert before
     * @return {Pipe}
     */

  }, {
    key: "insert",
    value: function insert(key, value, neighbour) {
      var after = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;

      if (!this.has(neighbour)) {
        throw new Error("No such neighbour key [".concat(neighbour, "]"));
      }

      var offset = after ? 1 : 0;
      var index = this.order.indexOf(neighbour);
      this.order.splice(index + offset, 0, key);
      return (0, _get2["default"])((0, _getPrototypeOf2["default"])(Pipe.prototype), "set", this).call(this, key, value);
    }
    /**
     * Insert a function before another
     *
     * @param {string|*} key The name of the hook
     * @param {Function} value The function to call
     * @param {string|*} neighbour The neighbour to insert before
     * @return {Pipe}
     */

  }, {
    key: "before",
    value: function before(key, value, neighbour) {
      return this.insert(key, value, neighbour, false);
    }
    /**
     * Insert a function after another
     *
     * @param {string|*} key The name of the hook
     * @param {Function} value The function to call
     * @param {string|*} neighbour The neighbour to insert after
     * @return {Pipe}
     */

  }, {
    key: "after",
    value: function after(key, value, neighbour) {
      return this.insert(key, value, neighbour, true);
    }
    /**
     * Remove a function from the stack
     *
     * @param {string|*} key
     * @return {boolean}
     */

  }, {
    key: "delete",
    value: function _delete(key) {
      this.order.filter(function (entry) {
        return entry !== key;
      });
      return (0, _get2["default"])((0, _getPrototypeOf2["default"])(Pipe.prototype), "delete", this).call(this, key);
    }
    /**
     * Clear the stack
     */

  }, {
    key: "clear",
    value: function clear() {
      this.order.length = 0;
      return (0, _get2["default"])((0, _getPrototypeOf2["default"])(Pipe.prototype), "clear", this).call(this);
    }
    /**
     * @inheritDoc
     */

  }, {
    key: "forEach",
    value: function forEach(callbackfn, thisArg) {
      for (var i = 0; i < this.order.length; i += 1) {
        var key = this.order[i];
        callbackfn.call(thisArg, key, this.get(key));
      }
    }
    /**
     * @inheritDoc
     */

  }, {
    key: _Symbol$iterator,
    value:
    /*#__PURE__*/
    _regenerator["default"].mark(function value() {
      var i, key;
      return _regenerator["default"].wrap(function value$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              i = 0;

            case 1:
              if (!(i < this.order.length)) {
                _context.next = 8;
                break;
              }

              key = this.order[i];
              _context.next = 5;
              return [key, this.get(key)];

            case 5:
              i += 1;
              _context.next = 1;
              break;

            case 8:
            case "end":
              return _context.stop();
          }
        }
      }, value, this);
    })
    /**
     * @inheritDoc
     */

  }, {
    key: "entries",
    value:
    /*#__PURE__*/
    _regenerator["default"].mark(function entries() {
      var i, key;
      return _regenerator["default"].wrap(function entries$(_context2) {
        while (1) {
          switch (_context2.prev = _context2.next) {
            case 0:
              i = 0;

            case 1:
              if (!(i < this.order.length)) {
                _context2.next = 8;
                break;
              }

              key = this.order[i];
              _context2.next = 5;
              return [key, this.get(key)];

            case 5:
              i += 1;
              _context2.next = 1;
              break;

            case 8:
            case "end":
              return _context2.stop();
          }
        }
      }, entries, this);
    })
    /**
     * @inheritDoc
     */

  }, {
    key: "keys",
    value:
    /*#__PURE__*/
    _regenerator["default"].mark(function keys() {
      var i;
      return _regenerator["default"].wrap(function keys$(_context3) {
        while (1) {
          switch (_context3.prev = _context3.next) {
            case 0:
              i = 0;

            case 1:
              if (!(i < this.order.length)) {
                _context3.next = 7;
                break;
              }

              _context3.next = 4;
              return this.order[i];

            case 4:
              i += 1;
              _context3.next = 1;
              break;

            case 7:
            case "end":
              return _context3.stop();
          }
        }
      }, keys, this);
    })
    /**
     * @inheritDoc
     */

  }, {
    key: "values",
    value:
    /*#__PURE__*/
    _regenerator["default"].mark(function values() {
      var i;
      return _regenerator["default"].wrap(function values$(_context4) {
        while (1) {
          switch (_context4.prev = _context4.next) {
            case 0:
              i = 0;

            case 1:
              if (!(i < this.order.length)) {
                _context4.next = 7;
                break;
              }

              _context4.next = 4;
              return this.get(this.order[i]);

            case 4:
              i += 1;
              _context4.next = 1;
              break;

            case 7:
            case "end":
              return _context4.stop();
          }
        }
      }, values, this);
    })
  }]);
  return Pipe;
}((0, _wrapNativeSuper2["default"])(Map));

exports["default"] = Pipe;