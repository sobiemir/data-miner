"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const fs = require("fs");
class Parser {
    constructor(file) {
        this._file = file;
        this._data = fs.readFileSync(file).toString();
        this._length = this._data.length;
        this._pos = 0;
    }
    NextNonWhiteChar() {
        let char;
        do
            char = this._data[++this._pos];
        while (char === "\t" || char === " " || char === "\r" || char === "\n");
    }
}
new Parser("../test/moss/tst/array_test.c");
//# sourceMappingURL=app.js.map