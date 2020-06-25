# Installation
```
npm i @teamawesome/transform
```
# Usage
```
import Pipe from '@teamawesome/transform

const p = new Pipe();
// Add a function at the end of the stack
p.add('fn1', () => { });
// Insert a function after another
p.insert('afterfn1', () => {}, 'fn1', true);
// Insert a function before another
p.insert('beforefn1', () => {}, 'fn1', false);

const value = p.call();
```
# Constructor
Can be given an iterable with entries, an object, or just a function. When just a function is given, it will be 
registered under the key "main".
```
new Pipe({
    first: () => {},
    second: () => {}
});

new Pipe(function () {
});

new Pipe(new Map(...));
```
# Methods
Pipe inherits from Map. Any function on Map will work on Pipe. All iteration methods iterate in the order that executing
it would. 

Additionally, there are the following methods:
* `insert(key, function, neighbour, after = true)`
* `call(args)` Will call the stack top to bottom, transforming args with each call. If one of the functions returns a
 Promise, `call` will also return a Promise.
