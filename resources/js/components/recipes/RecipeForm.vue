<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Recipe, RecipeFormData } from '@/types/recipe';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ImagePlus, Plus, Trash2, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount } from 'vue';

const props = defineProps<{
    /** 指定時は編集モードになる */
    recipe?: Recipe;
    submitLabel: string;
}>();

const isEdit = computed(() => props.recipe !== undefined);

// サーバー側（StoreRecipeRequest / UpdateRecipeRequest）の上限と揃える
const MAX_ITEMS = 50;
const MAX_STEP_IMAGES = 3;

const form = useForm<RecipeFormData>({
    title: props.recipe?.title ?? '',
    description: props.recipe?.description ?? '',
    recipe_image: null,
    cooking_time: props.recipe?.cooking_time ?? '',
    servings: props.recipe?.servings ?? '',
    ingredients: props.recipe?.ingredients?.map((ingredient) => ({ name: ingredient.name, quantity: ingredient.quantity })) ?? [
        { name: '', quantity: '' },
    ],
    steps: props.recipe?.steps?.map((step) => ({ description: step.description, images: [] })) ?? [{ description: '', images: [] }],
});

// ingredients.0.name のようなネストしたキーは useForm の errors 型に含まれないため、
// Record として引き直す
const errors = computed(() => form.errors as Record<string, string | undefined>);

const textareaClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

/** 配列の要素を1つ上（-1）または下（+1）に動かす */
const move = <T,>(items: T[], index: number, direction: -1 | 1) => {
    const target = index + direction;

    if (target < 0 || target >= items.length) {
        return;
    }

    [items[index], items[target]] = [items[target], items[index]];
};

const addIngredient = () => form.ingredients.push({ name: '', quantity: '' });
const removeIngredient = (index: number) => form.ingredients.splice(index, 1);

const addStep = () => form.steps.push({ description: '', images: [] });
const removeStep = (index: number) => form.steps.splice(index, 1);

// 選んだ画像のプレビュー。File ごとに object URL を作って使い回し、画面を離れるときに解放する
const previewUrls = new Map<File, string>();
const previewUrl = (file: File): string => {
    if (!previewUrls.has(file)) {
        previewUrls.set(file, URL.createObjectURL(file));
    }

    return previewUrls.get(file) as string;
};
onBeforeUnmount(() => previewUrls.forEach((url) => URL.revokeObjectURL(url)));

const recipeImagePreview = computed(() => (form.recipe_image ? previewUrl(form.recipe_image) : (props.recipe?.recipe_image_url ?? null)));

const onRecipeImageChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.recipe_image = input.files?.[0] ?? null;
};

const onStepImagesChange = (event: Event, index: number) => {
    const input = event.target as HTMLInputElement;
    form.steps[index].images = [...form.steps[index].images, ...Array.from(input.files ?? [])];
    // 同じファイルを選び直せるよう、選択状態はリセットして一覧は state 側で表示する
    input.value = '';
};

const removeStepImage = (stepIndex: number, imageIndex: number) => form.steps[stepIndex].images.splice(imageIndex, 1);

const cancelHref = computed(() => (props.recipe ? route('recipes.show', { recipe: props.recipe.id }) : route('recipes.index')));

const submit = () => {
    if (isEdit.value && props.recipe) {
        // multipart で送るため PUT ではなく POST + _method で送信する
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('recipes.update', { recipe: props.recipe.id }));

        return;
    }

    form.post(route('recipes.store'));
};
</script>

<template>
    <form @submit.prevent="submit" class="flex flex-col gap-6">
        <!-- 基本情報 -->
        <section class="flex flex-col gap-5 rounded-xl border bg-card p-5 sm:p-6">
            <h2 class="text-lg font-semibold">基本情報</h2>

            <div class="grid gap-2">
                <Label for="title">タイトル</Label>
                <Input id="title" type="text" maxlength="255" v-model="form.title" placeholder="例: 基本の肉じゃが" />
                <InputError :message="form.errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="description">説明</Label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    maxlength="2000"
                    :class="textareaClass"
                    placeholder="どんなレシピか一言で"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="recipe_image">完成写真</Label>
                <label
                    for="recipe_image"
                    class="relative flex aspect-video w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-muted/50 transition hover:border-primary"
                >
                    <img v-if="recipeImagePreview" :src="recipeImagePreview" alt="完成写真のプレビュー" class="h-full w-full object-cover" />
                    <span v-else class="flex flex-col items-center gap-2 text-sm text-muted-foreground">
                        <ImagePlus class="h-8 w-8" />
                        クリックして写真を選ぶ
                    </span>
                </label>
                <input id="recipe_image" type="file" accept="image/*" class="sr-only" @change="onRecipeImageChange" />
                <p v-if="recipe" class="text-xs text-muted-foreground">変更する場合のみ選び直してください。</p>
                <InputError :message="form.errors.recipe_image" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="cooking_time">調理時間（分）</Label>
                    <Input id="cooking_time" type="number" min="1" max="1440" v-model="form.cooking_time" placeholder="30" />
                    <InputError :message="form.errors.cooking_time" />
                </div>

                <div class="grid gap-2">
                    <Label for="servings">何人分</Label>
                    <Input id="servings" type="number" min="1" max="100" v-model="form.servings" placeholder="2" />
                    <InputError :message="form.errors.servings" />
                </div>
            </div>
        </section>

        <!-- 材料 -->
        <section class="flex flex-col gap-4 rounded-xl border bg-card p-5 sm:p-6">
            <h2 class="text-lg font-semibold">材料</h2>
            <InputError :message="form.errors.ingredients" />

            <div v-for="(ingredient, index) in form.ingredients" :key="index" class="flex flex-col gap-1">
                <div class="flex items-start gap-2">
                    <div class="min-w-0 flex-1">
                        <Input
                            type="text"
                            maxlength="255"
                            v-model="ingredient.name"
                            placeholder="じゃがいも"
                            :aria-label="`材料${index + 1}の名前`"
                        />
                        <InputError :message="errors[`ingredients.${index}.name`]" />
                    </div>
                    <div class="w-24 sm:w-32">
                        <Input type="text" maxlength="255" v-model="ingredient.quantity" placeholder="3個" :aria-label="`材料${index + 1}の分量`" />
                        <InputError :message="errors[`ingredients.${index}.quantity`]" />
                    </div>
                    <div class="flex shrink-0">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="index === 0"
                            :aria-label="`材料${index + 1}を上へ`"
                            @click="move(form.ingredients, index, -1)"
                        >
                            <ArrowUp class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="index === form.ingredients.length - 1"
                            :aria-label="`材料${index + 1}を下へ`"
                            @click="move(form.ingredients, index, 1)"
                        >
                            <ArrowDown class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="form.ingredients.length === 1"
                            :aria-label="`材料${index + 1}を削除`"
                            @click="removeIngredient(index)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <Button type="button" variant="outline" class="self-start" :disabled="form.ingredients.length >= MAX_ITEMS" @click="addIngredient">
                <Plus class="h-4 w-4" />
                材料を追加
            </Button>
        </section>

        <!-- 作り方 -->
        <section class="flex flex-col gap-4 rounded-xl border bg-card p-5 sm:p-6">
            <h2 class="text-lg font-semibold">作り方</h2>
            <InputError :message="form.errors.steps" />
            <p v-if="recipe" class="text-xs text-muted-foreground">画像を選び直さない手順は、更新時に画像なしになります。</p>

            <div v-for="(step, index) in form.steps" :key="index" class="flex flex-col gap-3 rounded-lg border p-4">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-sm font-medium">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">{{
                            index + 1
                        }}</span>
                        手順 {{ index + 1 }}
                    </span>
                    <div class="flex">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="index === 0"
                            :aria-label="`手順${index + 1}を上へ`"
                            @click="move(form.steps, index, -1)"
                        >
                            <ArrowUp class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="index === form.steps.length - 1"
                            :aria-label="`手順${index + 1}を下へ`"
                            @click="move(form.steps, index, 1)"
                        >
                            <ArrowDown class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="form.steps.length === 1"
                            :aria-label="`手順${index + 1}を削除`"
                            @click="removeStep(index)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <textarea
                    v-model="step.description"
                    rows="2"
                    maxlength="2000"
                    :class="textareaClass"
                    placeholder="材料を切る"
                    :aria-label="`手順${index + 1}の説明`"
                />
                <InputError :message="errors[`steps.${index}.description`]" />

                <div class="flex flex-wrap gap-2">
                    <div v-for="(image, imageIndex) in step.images" :key="imageIndex" class="relative">
                        <img :src="previewUrl(image)" :alt="`手順${index + 1}の画像${imageIndex + 1}`" class="h-20 w-20 rounded-md object-cover" />
                        <button
                            type="button"
                            class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-foreground text-background shadow"
                            :aria-label="`手順${index + 1}の画像${imageIndex + 1}を外す`"
                            @click="removeStepImage(index, imageIndex)"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <label
                        class="flex h-20 w-20 flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed text-xs text-muted-foreground transition"
                        :class="step.images.length >= MAX_STEP_IMAGES ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:border-primary'"
                    >
                        <ImagePlus class="h-5 w-5" />
                        画像
                        <input
                            type="file"
                            accept="image/*"
                            multiple
                            class="sr-only"
                            :disabled="step.images.length >= MAX_STEP_IMAGES"
                            @change="(event: Event) => onStepImagesChange(event, index)"
                        />
                    </label>
                </div>
                <InputError :message="errors[`steps.${index}.images`] ?? errors[`steps.${index}.images.0`]" />
            </div>

            <Button type="button" variant="outline" class="self-start" :disabled="form.steps.length >= MAX_ITEMS" @click="addStep">
                <Plus class="h-4 w-4" />
                手順を追加
            </Button>
        </section>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="ghost" as-child>
                <Link :href="cancelHref">キャンセル</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">{{ submitLabel }}</Button>
        </div>
    </form>
</template>
