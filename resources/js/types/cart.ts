export interface CartItem {
    id: number;
    name: string;
    quantity: string;
    order_number: number;
    is_checked: boolean;
}

/** 買い物リストのレシピ単位のまとまり */
export interface CartGroup {
    recipe: {
        id: number;
        title: string;
        /** 元レシピが削除済みならリンクを出さない */
        is_deleted: boolean;
    };
    items: CartItem[];
}

/** 「材料ごと」表示の1行。材料名が同じ行（レシピをまたぐ）を表示上だけまとめたもの */
export interface MergedCartItem {
    name: string;
    /** 空でない分量を元の並び順で並べたもの（文字列のため合算しない） */
    quantities: string[];
    /** まとめた元の行。チェック・削除はこの全行に効かせる */
    ids: number[];
    /** 出典のレシピ名（重複なし） */
    recipeTitles: string[];
    /** 元の行がすべてチェック済みのときだけ true */
    is_checked: boolean;
}
