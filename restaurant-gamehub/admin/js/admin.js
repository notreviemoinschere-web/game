(function () {
    // Minimal QR generator (qrcode-generator, MIT)
    function qrcode(typeNumber, errorCorrectLevel) {
        const PAD0 = 0xec;
        const PAD1 = 0x11;
        const _qr = {
            typeNumber: typeNumber,
            errorCorrectLevel: errorCorrectLevel,
            modules: null,
            moduleCount: 0,
            dataCache: null,
            dataList: [],
            addData: function (data) {
                const newData = {
                    mode: 4,
                    data: data,
                    getLength: function () { return this.data.length; },
                    write: function (buffer) {
                        for (let i = 0; i < this.data.length; i += 1) {
                            buffer.put(this.data.charCodeAt(i), 8);
                        }
                    },
                };
                this.dataList.push(newData);
                this.dataCache = null;
            },
            isDark: function (row, col) {
                if (row < 0 || this.moduleCount <= row || col < 0 || this.moduleCount <= col) {
                    throw new Error(row + ',' + col);
                }
                return this.modules[row][col];
            },
            getModuleCount: function () { return this.moduleCount; },
            make: function () {
                if (this.typeNumber < 1) {
                    let typeNumber = 1;
                    for (typeNumber = 1; typeNumber < 10; typeNumber += 1) {
                        const rsBlocks = QRRSBlock.getRSBlocks(typeNumber, this.errorCorrectLevel);
                        const buffer = new QRBitBuffer();
                        for (let i = 0; i < this.dataList.length; i += 1) {
                            const data = this.dataList[i];
                            buffer.put(data.mode, 4);
                            buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
                            data.write(buffer);
                        }
                        let totalDataCount = 0;
                        for (let i = 0; i < rsBlocks.length; i += 1) {
                            totalDataCount += rsBlocks[i].dataCount;
                        }
                        if (buffer.getLengthInBits() <= totalDataCount * 8) {
                            this.typeNumber = typeNumber;
                            break;
                        }
                    }
                }
                this.makeImpl(false, this.getBestMaskPattern());
            },
            makeImpl: function (test, maskPattern) {
                this.moduleCount = this.typeNumber * 4 + 17;
                this.modules = new Array(this.moduleCount);
                for (let row = 0; row < this.moduleCount; row += 1) {
                    this.modules[row] = new Array(this.moduleCount);
                    for (let col = 0; col < this.moduleCount; col += 1) {
                        this.modules[row][col] = null;
                    }
                }
                this.setupPositionProbePattern(0, 0);
                this.setupPositionProbePattern(this.moduleCount - 7, 0);
                this.setupPositionProbePattern(0, this.moduleCount - 7);
                this.setupPositionAdjustPattern();
                this.setupTimingPattern();
                this.setupTypeInfo(test, maskPattern);
                if (this.typeNumber >= 7) {
                    this.setupTypeNumber(test);
                }
                if (this.dataCache === null) {
                    this.dataCache = QRCodeModel.createData(this.typeNumber, this.errorCorrectLevel, this.dataList);
                }
                this.mapData(this.dataCache, maskPattern);
            },
            setupPositionProbePattern: function (row, col) {
                for (let r = -1; r <= 7; r += 1) {
                    if (row + r <= -1 || this.moduleCount <= row + r) continue;
                    for (let c = -1; c <= 7; c += 1) {
                        if (col + c <= -1 || this.moduleCount <= col + c) continue;
                        if ((0 <= r && r <= 6 && (c === 0 || c === 6)) || (0 <= c && c <= 6 && (r === 0 || r === 6)) || (2 <= r && r <= 4 && 2 <= c && c <= 4)) {
                            this.modules[row + r][col + c] = true;
                        } else {
                            this.modules[row + r][col + c] = false;
                        }
                    }
                }
            },
            getBestMaskPattern: function () {
                let minLostPoint = 0;
                let pattern = 0;
                for (let i = 0; i < 8; i += 1) {
                    this.makeImpl(true, i);
                    const lostPoint = QRUtil.getLostPoint(this);
                    if (i === 0 || minLostPoint > lostPoint) {
                        minLostPoint = lostPoint;
                        pattern = i;
                    }
                }
                return pattern;
            },
            setupTimingPattern: function () {
                for (let r = 8; r < this.moduleCount - 8; r += 1) {
                    if (this.modules[r][6] !== null) {
                        continue;
                    }
                    this.modules[r][6] = r % 2 === 0;
                }
                for (let c = 8; c < this.moduleCount - 8; c += 1) {
                    if (this.modules[6][c] !== null) {
                        continue;
                    }
                    this.modules[6][c] = c % 2 === 0;
                }
            },
            setupPositionAdjustPattern: function () {
                const pos = QRUtil.getPatternPosition(this.typeNumber);
                for (let i = 0; i < pos.length; i += 1) {
                    for (let j = 0; j < pos.length; j += 1) {
                        const row = pos[i];
                        const col = pos[j];
                        if (this.modules[row][col] !== null) {
                            continue;
                        }
                        for (let r = -2; r <= 2; r += 1) {
                            for (let c = -2; c <= 2; c += 1) {
                                if (r === -2 || r === 2 || c === -2 || c === 2 || (r === 0 && c === 0)) {
                                    this.modules[row + r][col + c] = true;
                                } else {
                                    this.modules[row + r][col + c] = false;
                                }
                            }
                        }
                    }
                }
            },
            setupTypeNumber: function (test) {
                const bits = QRUtil.getBCHTypeNumber(this.typeNumber);
                for (let i = 0; i < 18; i += 1) {
                    const mod = (!test && ((bits >> i) & 1) === 1);
                    this.modules[Math.floor(i / 3)][i % 3 + this.moduleCount - 8 - 3] = mod;
                }
                for (let i = 0; i < 18; i += 1) {
                    const mod = (!test && ((bits >> i) & 1) === 1);
                    this.modules[i % 3 + this.moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
                }
            },
            setupTypeInfo: function (test, maskPattern) {
                const data = (this.errorCorrectLevel << 3) | maskPattern;
                const bits = QRUtil.getBCHTypeInfo(data);
                for (let i = 0; i < 15; i += 1) {
                    const mod = (!test && ((bits >> i) & 1) === 1);
                    if (i < 6) {
                        this.modules[i][8] = mod;
                    } else if (i < 8) {
                        this.modules[i + 1][8] = mod;
                    } else {
                        this.modules[this.moduleCount - 15 + i][8] = mod;
                    }
                }
                for (let i = 0; i < 15; i += 1) {
                    const mod = (!test && ((bits >> i) & 1) === 1);
                    if (i < 8) {
                        this.modules[8][this.moduleCount - i - 1] = mod;
                    } else if (i < 9) {
                        this.modules[8][15 - i - 1 + 1] = mod;
                    } else {
                        this.modules[8][15 - i - 1] = mod;
                    }
                }
                this.modules[this.moduleCount - 8][8] = !test;
            },
            mapData: function (data, maskPattern) {
                let inc = -1;
                let row = this.moduleCount - 1;
                let bitIndex = 7;
                let byteIndex = 0;
                const maskFunc = QRUtil.getMaskFunction(maskPattern);
                for (let col = this.moduleCount - 1; col > 0; col -= 2) {
                    if (col === 6) col -= 1;
                    while (true) {
                        for (let c = 0; c < 2; c += 1) {
                            if (this.modules[row][col - c] === null) {
                                let dark = false;
                                if (byteIndex < data.length) {
                                    dark = (((data[byteIndex] >>> bitIndex) & 1) === 1);
                                }
                                const mask = maskFunc(row, col - c);
                                if (mask) {
                                    dark = !dark;
                                }
                                this.modules[row][col - c] = dark;
                                bitIndex -= 1;
                                if (bitIndex === -1) {
                                    byteIndex += 1;
                                    bitIndex = 7;
                                }
                            }
                        }
                        row += inc;
                        if (row < 0 || this.moduleCount <= row) {
                            row -= inc;
                            inc = -inc;
                            break;
                        }
                    }
                }
            }
        };
        return _qr;
    }

    function QRBitBuffer() {
        this.buffer = [];
        this.length = 0;
    }
    QRBitBuffer.prototype = {
        get: function (index) {
            const bufIndex = Math.floor(index / 8);
            return ((this.buffer[bufIndex] >>> (7 - index % 8)) & 1) === 1;
        },
        put: function (num, length) {
            for (let i = 0; i < length; i += 1) {
                this.putBit(((num >>> (length - i - 1)) & 1) === 1);
            }
        },
        putBit: function (bit) {
            const bufIndex = Math.floor(this.length / 8);
            if (this.buffer.length <= bufIndex) {
                this.buffer.push(0);
            }
            if (bit) {
                this.buffer[bufIndex] |= (0x80 >>> (this.length % 8));
            }
            this.length += 1;
        },
        getLengthInBits: function () {
            return this.length;
        }
    };

    const QRUtil = {
        PATTERN_POSITION_TABLE: [
            [], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34], [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50]
        ],
        G15: 0x0537,
        G18: 0x1f25,
        G15_MASK: 0x5412,
        getBCHTypeInfo: function (data) {
            let d = data << 10;
            while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15) >= 0) {
                d ^= (QRUtil.G15 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15)));
            }
            return ((data << 10) | d) ^ QRUtil.G15_MASK;
        },
        getBCHTypeNumber: function (data) {
            let d = data << 12;
            while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18) >= 0) {
                d ^= (QRUtil.G18 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18)));
            }
            return (data << 12) | d;
        },
        getBCHDigit: function (data) {
            let digit = 0;
            while (data !== 0) {
                digit += 1;
                data >>>= 1;
            }
            return digit;
        },
        getPatternPosition: function (typeNumber) {
            return QRUtil.PATTERN_POSITION_TABLE[typeNumber - 1];
        },
        getMaskFunction: function (maskPattern) {
            return function (i, j) {
                switch (maskPattern) {
                case 0: return (i + j) % 2 === 0;
                case 1: return i % 2 === 0;
                case 2: return j % 3 === 0;
                case 3: return (i + j) % 3 === 0;
                case 4: return (Math.floor(i / 2) + Math.floor(j / 3)) % 2 === 0;
                case 5: return (i * j) % 2 + (i * j) % 3 === 0;
                case 6: return ((i * j) % 2 + (i * j) % 3) % 2 === 0;
                case 7: return ((i * j) % 3 + (i + j) % 2) % 2 === 0;
                default: return false;
                }
            };
        },
        getErrorCorrectPolynomial: function (errorCorrectLength) {
            let a = QRPolynomial([1], 0);
            for (let i = 0; i < errorCorrectLength; i += 1) {
                a = a.multiply(QRPolynomial([1, QRMath.gexp(i)], 0));
            }
            return a;
        },
        getLengthInBits: function (mode, type) {
            if (1 <= type && type < 10) {
                return 8;
            }
            return 16;
        },
        getLostPoint: function (qrCode) {
            const moduleCount = qrCode.getModuleCount();
            let lostPoint = 0;
            for (let row = 0; row < moduleCount; row += 1) {
                for (let col = 0; col < moduleCount; col += 1) {
                    let sameCount = 0;
                    const dark = qrCode.isDark(row, col);
                    for (let r = -1; r <= 1; r += 1) {
                        if (row + r < 0 || moduleCount <= row + r) continue;
                        for (let c = -1; c <= 1; c += 1) {
                            if (col + c < 0 || moduleCount <= col + c) continue;
                            if (r === 0 && c === 0) continue;
                            if (dark === qrCode.isDark(row + r, col + c)) sameCount += 1;
                        }
                    }
                    if (sameCount > 5) {
                        lostPoint += (3 + sameCount - 5);
                    }
                }
            }
            return lostPoint;
        }
    };

    function QRPolynomial(num, shift) {
        let offset = 0;
        while (offset < num.length && num[offset] === 0) {
            offset += 1;
        }
        this.num = new Array(num.length - offset + shift);
        for (let i = 0; i < num.length - offset; i += 1) {
            this.num[i] = num[i + offset];
        }
    }
    QRPolynomial.prototype = {
        get: function (index) { return this.num[index]; },
        getLength: function () { return this.num.length; },
        multiply: function (e) {
            const num = new Array(this.getLength() + e.getLength() - 1).fill(0);
            for (let i = 0; i < this.getLength(); i += 1) {
                for (let j = 0; j < e.getLength(); j += 1) {
                    num[i + j] ^= QRMath.gexp(QRMath.glog(this.get(i)) + QRMath.glog(e.get(j)));
                }
            }
            return QRPolynomial(num, 0);
        },
        mod: function (e) {
            if (this.getLength() - e.getLength() < 0) {
                return this;
            }
            const ratio = QRMath.glog(this.get(0)) - QRMath.glog(e.get(0));
            const num = new Array(this.getLength());
            for (let i = 0; i < this.getLength(); i += 1) {
                num[i] = this.get(i);
            }
            for (let i = 0; i < e.getLength(); i += 1) {
                num[i] ^= QRMath.gexp(QRMath.glog(e.get(i)) + ratio);
            }
            return QRPolynomial(num, 0).mod(e);
        }
    };

    const QRMath = {
        glog: function (n) {
            if (n < 1) throw new Error('glog');
            return QRMath.LOG_TABLE[n];
        },
        gexp: function (n) {
            while (n < 0) n += 255;
            while (n >= 256) n -= 255;
            return QRMath.EXP_TABLE[n];
        },
        EXP_TABLE: new Array(256),
        LOG_TABLE: new Array(256)
    };
    for (let i = 0; i < 8; i += 1) {
        QRMath.EXP_TABLE[i] = 1 << i;
    }
    for (let i = 8; i < 256; i += 1) {
        QRMath.EXP_TABLE[i] = QRMath.EXP_TABLE[i - 4] ^ QRMath.EXP_TABLE[i - 5] ^ QRMath.EXP_TABLE[i - 6] ^ QRMath.EXP_TABLE[i - 8];
    }
    for (let i = 0; i < 255; i += 1) {
        QRMath.LOG_TABLE[QRMath.EXP_TABLE[i]] = i;
    }

    function QRRSBlock(totalCount, dataCount) {
        this.totalCount = totalCount;
        this.dataCount = dataCount;
    }
    QRRSBlock.getRSBlocks = function (typeNumber, errorCorrectLevel) {
        return [new QRRSBlock(26, 19)];
    };

    const QRCodeModel = {
        createData: function (typeNumber, errorCorrectLevel, dataList) {
            const rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectLevel);
            const buffer = new QRBitBuffer();
            for (let i = 0; i < dataList.length; i += 1) {
                const data = dataList[i];
                buffer.put(data.mode, 4);
                buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
                data.write(buffer);
            }
            let totalDataCount = 0;
            for (let i = 0; i < rsBlocks.length; i += 1) {
                totalDataCount += rsBlocks[i].dataCount;
            }
            if (buffer.getLengthInBits() > totalDataCount * 8) {
                throw new Error('code length overflow');
            }
            if (buffer.getLengthInBits() + 4 <= totalDataCount * 8) {
                buffer.put(0, 4);
            }
            while (buffer.getLengthInBits() % 8 !== 0) {
                buffer.putBit(false);
            }
            while (buffer.getLengthInBits() < totalDataCount * 8) {
                buffer.put(PAD0, 8);
                if (buffer.getLengthInBits() >= totalDataCount * 8) {
                    break;
                }
                buffer.put(PAD1, 8);
            }
            return QRCodeModel.createBytes(buffer, rsBlocks);
        },
        createBytes: function (buffer, rsBlocks) {
            let offset = 0;
            const maxDcCount = 0;
            const maxEcCount = 0;
            const dcdata = [];
            const ecdata = [];
            for (let r = 0; r < rsBlocks.length; r += 1) {
                const dcCount = rsBlocks[r].dataCount;
                const ecCount = rsBlocks[r].totalCount - dcCount;
                dcdata[r] = new Array(dcCount);
                for (let i = 0; i < dcdata[r].length; i += 1) {
                    dcdata[r][i] = 0xff & buffer.buffer[i + offset];
                }
                offset += dcCount;
                const rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
                const rawPoly = QRPolynomial(dcdata[r], rsPoly.getLength() - 1);
                const modPoly = rawPoly.mod(rsPoly);
                ecdata[r] = new Array(rsPoly.getLength() - 1);
                for (let i = 0; i < ecdata[r].length; i += 1) {
                    const modIndex = i + modPoly.getLength() - ecdata[r].length;
                    ecdata[r][i] = (modIndex >= 0) ? modPoly.get(modIndex) : 0;
                }
            }
            const totalCodeCount = rsBlocks[0].totalCount;
            const data = new Array(totalCodeCount);
            let index = 0;
            for (let i = 0; i < dcdata[0].length; i += 1) {
                data[index] = dcdata[0][i];
                index += 1;
            }
            for (let i = 0; i < ecdata[0].length; i += 1) {
                data[index] = ecdata[0][i];
                index += 1;
            }
            return data;
        }
    };

    function renderQR(container, text) {
        const qr = qrcode(1, 1);
        qr.addData(text);
        qr.make();
        const size = 200;
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = size;
        canvas.height = size;
        const count = qr.getModuleCount();
        const cell = size / count;
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = '#000';
        for (let r = 0; r < count; r += 1) {
            for (let c = 0; c < count; c += 1) {
                if (qr.isDark(r, c)) {
                    ctx.fillRect(c * cell, r * cell, cell, cell);
                }
            }
        }
        container.innerHTML = '';
        container.appendChild(canvas);
        return canvas;
    }

    const qrContainer = document.querySelector('.gamehub-qr');
    if (qrContainer) {
        const url = qrContainer.dataset.qrUrl || '';
        const canvas = renderQR(qrContainer, url);

        document.querySelectorAll('[data-copy-link]').forEach((button) => {
            button.addEventListener('click', () => {
                navigator.clipboard.writeText(url);
                button.textContent = 'Copié';
                setTimeout(() => { button.textContent = 'Copier le lien'; }, 1200);
            });
        });

        document.querySelectorAll('[data-download-qr]').forEach((button) => {
            button.addEventListener('click', () => {
                const link = document.createElement('a');
                link.download = 'gamehub-qr.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });

        document.querySelectorAll('[data-print-qr]').forEach((button) => {
            button.addEventListener('click', () => {
                const w = window.open('', 'print');
                w.document.write('<h2>QR Code</h2>');
                w.document.write('<p>' + url + '</p>');
                w.document.write('<img src="' + canvas.toDataURL('image/png') + '" />');
                w.document.close();
                w.focus();
                w.print();
            });
        });
    }

    const modal = document.getElementById('gamehub-prize-modal');
    if (modal) {
        document.querySelectorAll('[data-open-prize-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                modal.hidden = false;
                modal.querySelector('input[name="prize_id"]').value = button.dataset.prizeId || '';
                modal.querySelector('input[name="title"]').value = button.dataset.prizeTitle || '';
                modal.querySelector('select[name="type"]').value = button.dataset.prizeType || button.dataset.type || 'win';
                modal.querySelector('input[name="weight"]').value = button.dataset.prizeWeight || 10;
                modal.querySelector('input[name="stock"]').value = button.dataset.prizeStock || '';
                modal.querySelector('input[name="expiry_days"]').value = button.dataset.prizeExpiry || '';
            });
        });
        modal.querySelector('[data-close-modal]').addEventListener('click', () => {
            modal.hidden = true;
        });
    }
})();
