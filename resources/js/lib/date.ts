const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

/**
 * Y-m-d を「11/26（木）」の形にする。ブラウザのタイムゾーンでずれないよう、文字列から直接組み立てる。
 */
export const formatMonthDayWithWeekday = (date: string): string => {
    const [year, month, day] = date.split('-').map(Number);
    const weekday = WEEKDAYS[new Date(Date.UTC(year, month - 1, day)).getUTCDay()];

    return `${month}/${day}（${weekday}）`;
};
