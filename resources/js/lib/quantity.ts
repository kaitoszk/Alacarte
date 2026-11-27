/** 整数・小数・分数（1/2）のどれか */
const NUMBER = String.raw`\d+(?:\.\d+)?(?:\/\d+)?`;

/** 最初に出てくる数値。直後に「〜3」のような範囲が続けば、その数値も含めて拾う */
const FIRST_NUMBER = new RegExp(`(${NUMBER})(\\s*[〜~～]\\s*)?(${NUMBER})?`);

/** 分数として見せる端数。レシピでは小数より分数のほうが読みやすい */
const FRACTIONS: ReadonlyArray<readonly [number, number]> = [
    [1, 2],
    [1, 3],
    [2, 3],
    [1, 4],
    [3, 4],
];

const TOLERANCE = 0.02;

const parseNumber = (text: string): number => {
    const [numerator, denominator] = text.split('/');
    return denominator === undefined ? Number(numerator) : Number(numerator) / Number(denominator);
};

const formatNumber = (value: number): string => {
    const rounded = Math.round(value);
    if (Math.abs(value - rounded) < TOLERANCE) {
        return String(rounded);
    }

    // 「66と2/3g」のような表記は読みにくいので、大きい値は整数に丸める
    if (value >= 10) {
        return String(rounded);
    }

    const whole = Math.floor(value);
    const fraction = FRACTIONS.find(([n, d]) => Math.abs(value - whole - n / d) < TOLERANCE);
    if (fraction) {
        const [n, d] = fraction;
        return whole > 0 ? `${whole}と${n}/${d}` : `${n}/${d}`;
    }

    // ごく少量でも「0」と出さない
    return String(Math.max(Math.round(value * 10) / 10, 0.1));
};

/**
 * 分量の文字列に含まれる最初の数値を ratio 倍した文字列を返す。
 * 「大さじ1」「1/2個」「2〜3個」に対応し、数値を含まない「少々」などはそのまま返す。
 */
export const scaleQuantity = (quantity: string, ratio: number): string => {
    if (ratio === 1) {
        return quantity;
    }

    return quantity.replace(FIRST_NUMBER, (_match, first: string, separator?: string, second?: string) => {
        const scaledFirst = formatNumber(parseNumber(first) * ratio);
        if (separator === undefined || second === undefined) {
            // 「2〜」のように区切りだけ続く場合は区切りを残す
            return scaledFirst + (separator ?? '');
        }
        return `${scaledFirst}${separator}${formatNumber(parseNumber(second) * ratio)}`;
    });
};
