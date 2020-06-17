import { isPromise } from './util';

export default class Pipe extends Map {
  /**
   * @type {*[]}
   */
  order = [];

  /**
   * @param {Function|Iterable|Object} wrapped
   * @return {Function}
   */
  constructor(wrapped = {}) {
    super();

    let entries;
    if (wrapped[Symbol.iterator]) {
      entries = [...wrapped.entries()];
    } else if (typeof wrapped === 'object') {
      entries = Object.entries(wrapped);
    } else if (typeof wrapped === 'function') {
      entries = [['main', wrapped]];
    }

    entries.forEach(([key, value]) => this.set(key, value));
  }

  /**
   * @param args
   * @param thisArg
   */
  call(args, thisArg) {
    return this.order.reduce((value, action) => {
      const func = this.get(action);

      if (isPromise(value)) {
        return Promise.resolve(value).then(
          resolvedValue => func.call(thisArg, resolvedValue),
        );
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
  set(key, value) {
    if (!this.has(key)) {
      this.order.push(key);
    }

    return super.set(key, value);
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
  insert(key, value, neighbour, after = true) {
    if (!this.has(neighbour)) {
      throw new Error(`No such neighbour key [${neighbour}]`);
    }

    const offset = after ? 1 : 0;
    const index = this.order.indexOf(neighbour);

    this.order.splice(index + offset, 0, key);
    return super.set(key, value);
  }

  /**
   * Insert a function before another
   *
   * @param {string|*} key The name of the hook
   * @param {Function} value The function to call
   * @param {string|*} neighbour The neighbour to insert before
   * @return {Pipe}
   */
  before(key, value, neighbour) {
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
  after(key, value, neighbour) {
    return this.insert(key, value, neighbour, true);
  }

  /**
   * Remove a function from the stack
   *
   * @param {string|*} key
   * @return {boolean}
   */
  delete(key) {
    this.order.filter(entry => entry !== key);

    return super.delete(key);
  }

  /**
   * Clear the stack
   */
  clear() {
    this.order.length = 0;

    return super.clear();
  }


  /**
   * @inheritDoc
   */
  forEach(callbackfn, thisArg) {
    for (let i = 0; i < this.order.length; i += 1) {
      const key = this.order[i];

      callbackfn.call(thisArg, key, this.get(key));
    }
  }

  /**
   * @inheritDoc
   */
  * [Symbol.iterator]() {
    for (let i = 0; i < this.order.length; i += 1) {
      const key = this.order[i];

      yield [key, this.get(key)];
    }
  }

  /**
   * @inheritDoc
   */
  * entries() {
    for (let i = 0; i < this.order.length; i += 1) {
      const key = this.order[i];

      yield [key, this.get(key)];
    }
  }

  /**
   * @inheritDoc
   */
  * keys() {
    for (let i = 0; i < this.order.length; i += 1) {
      yield this.order[i];
    }
  }

  /**
   * @inheritDoc
   */
  * values() {
    for (let i = 0; i < this.order.length; i += 1) {
      yield this.get(this.order[i]);
    }
  }
}
