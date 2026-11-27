import type { CartGroup, MergedCartItem } from '@/types/cart';

/**
 * レシピ別のまとまりを、材料名（前後の空白を除いた完全一致）ごとの行にまとめる。
 * 並び順は、元の並び（新しく追加したレシピが上、レシピ内は材料の順）で最初に出てきた位置。
 */
export const mergeCartItems = (groups: CartGroup[]): MergedCartItem[] => {
    const merged = new Map<string, MergedCartItem>();

    for (const group of groups) {
        for (const item of group.items) {
            const name = item.name.trim();
            const row = merged.get(name) ?? { name, quantities: [], ids: [], recipeTitles: [], is_checked: true };

            row.ids.push(item.id);
            if (item.quantity.trim() !== '') {
                row.quantities.push(item.quantity.trim());
            }
            if (!row.recipeTitles.includes(group.recipe.title)) {
                row.recipeTitles.push(group.recipe.title);
            }
            row.is_checked = row.is_checked && item.is_checked;

            merged.set(name, row);
        }
    }

    return [...merged.values()];
};
