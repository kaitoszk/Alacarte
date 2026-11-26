/** 献立の1件（ある日に作るレシピ） */
export interface MealPlan {
    id: number;
    /** Y-m-d */
    date: string;
    recipe: {
        id: number;
        title: string;
        recipe_image_url: string | null;
    };
}

/** 献立ページの1日分。献立がない日は meal_plans が空 */
export interface MealPlanDay {
    /** Y-m-d */
    date: string;
    meal_plans: MealPlan[];
}
