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
