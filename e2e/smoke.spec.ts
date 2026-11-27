import { expect, test, type Page } from '@playwright/test';

/**
 * ページ上で発生したコンソールエラーと未処理例外を集める。
 * Vue のマウント失敗（画面が真っ白／真っ黒になるやつ）はここに出る。
 */
const collectPageErrors = (page: Page): string[] => {
    const errors: string[] = [];

    page.on('console', (message) => {
        if (message.type() === 'error') {
            errors.push(`console.error: ${message.text()}`);
        }
    });

    page.on('pageerror', (error) => {
        errors.push(`pageerror: ${error.message}`);
    });

    page.on('requestfailed', (request) => {
        // 遅延読み込み中の画像が画面遷移で中断されたもの（ERR_ABORTED）は描画の不具合ではないので拾わない。
        // 404 などの読み込み失敗は console.error 側に出る
        if (request.failure()?.errorText === 'net::ERR_ABORTED') {
            return;
        }
        errors.push(`requestfailed: ${request.url()} (${request.failure()?.errorText})`);
    });

    return errors;
};

/** 一覧に並ぶレシピカードのリンク（「レシピを投稿」ボタンなどを拾わないよう画像付きに限定する） */
const recipeCards = (page: Page) => page.locator('a[href*="/recipes/"]:has(img)');

/** レシピ詳細の材料名の一覧 */
const ingredientNames = (page: Page) => page.locator('section:has(> div > h2:has-text("材料")) ul > li > span:first-child');

/** 買い物リストの「レシピごと」表示で、そのレシピの行を全部消す（前回の実行の残りを片付けて、何度でも同じ状態から始めるため） */
const removeRecipeFromCart = async (page: Page, href: string) => {
    await page.goto('/shopping-list');
    // count() は待たないので、描画が終わってから数える
    await expect(page.getByRole('heading', { name: '買い物リスト' })).toBeVisible();
    const tab = page.getByRole('tab', { name: 'レシピごと' });
    if ((await tab.count()) === 0) {
        return; // 空のリストには切替が出ない
    }
    await tab.click();
    const deleteButtons = page
        .locator('section')
        .filter({ has: page.locator(`a[href="${href}"]`) })
        .getByRole('button', { name: /を削除$/ });
    for (let remaining = await deleteButtons.count(); remaining > 0; remaining--) {
        await deleteButtons.first().click();
        await expect(deleteButtons).toHaveCount(remaining - 1);
    }
};

const login = async (page: Page) => {
    await page.goto('/login');
    await page.getByLabel('メールアドレス').fill('test@example.com');
    await page.getByLabel('パスワード', { exact: true }).fill('password');
    await page.getByRole('button', { name: 'ログイン', exact: true }).click();
    // /dashboard は自分のプロフィール（マイページ）へリダイレクトする
    await page.waitForURL('**/users/**');
};

test('ゲストでもTopのみんなのレシピが描画される', async ({ page }) => {
    const errors = collectPageErrors(page);

    await page.goto('/');

    await expect(page.getByRole('heading', { name: '今日、何つくる？' })).toBeVisible();
    await expect(recipeCards(page).first().locator('img')).toBeVisible();

    expect(errors).toEqual([]);
});

test('Topのタブを切り替えられる', async ({ page }) => {
    const errors = collectPageErrors(page);

    await page.goto('/');

    // ゲストのフォロー中タブはログイン導線が出る
    await page.getByRole('tab', { name: 'フォロー中' }).click();
    await expect(page.getByText('ログインすると、フォロー中のユーザーのレシピが並びます。')).toBeVisible();

    // みんなのレシピに戻せる
    await page.getByRole('tab', { name: 'みんなのレシピ' }).click();
    await expect(recipeCards(page).first().locator('img')).toBeVisible();

    expect(errors).toEqual([]);
});

test('ログインするとフォロー中タブにレシピが並ぶ', async ({ page }) => {
    const errors = collectPageErrors(page);

    await login(page);
    await page.goto('/?tab=following');

    // フォロー中のうち少なくとも1人はレシピを持つ（シードで保証）
    await expect(recipeCards(page).first().locator('img')).toBeVisible();

    expect(errors).toEqual([]);
});

test('ゲストでもレシピ一覧が描画される', async ({ page }) => {
    const errors = collectPageErrors(page);

    await page.goto('/recipes');

    await expect(page.getByRole('heading', { name: 'レシピ', exact: true })).toBeVisible();
    // シード済みのレシピカードが並んでいること
    await expect(recipeCards(page).first().locator('img')).toBeVisible();
    await expect(page.getByText('まだレシピがありません。')).toHaveCount(0);

    expect(errors).toEqual([]);
});

test('ゲストでもレシピ詳細が描画される', async ({ page }) => {
    const errors = collectPageErrors(page);

    await page.goto('/recipes');
    await recipeCards(page).first().click();

    await expect(page.getByRole('heading', { name: '材料（' })).toBeVisible();
    await expect(page.getByRole('heading', { name: '作り方' })).toBeVisible();

    expect(errors).toEqual([]);
});

test('ログインするとレシピ投稿フォームが開く', async ({ page }) => {
    const errors = collectPageErrors(page);

    await login(page);
    await page.goto('/recipes/create');

    await expect(page.getByRole('heading', { name: 'レシピを投稿' })).toBeVisible();
    await expect(page.getByLabel('タイトル')).toBeVisible();
    await expect(page.getByRole('button', { name: '下書き保存' })).toBeVisible();
    await expect(page.getByRole('button', { name: '公開する' })).toBeVisible();

    expect(errors).toEqual([]);
});

test('投稿者をフォロー・解除できる', async ({ page }) => {
    const errors = collectPageErrors(page);

    await login(page);

    // 他人のレシピを探す（自分のレシピにはフォローボタンが出ない）
    await page.goto('/recipes');
    for (const card of await recipeCards(page).all()) {
        if (!(await card.innerText()).includes('Test User')) {
            await card.click();
            break;
        }
    }

    // 「フォロー」「フォロー中」どちらの状態でも同じボタンを掴む
    const toggle = page.getByRole('button', { name: /^フォロー/ });
    // isVisible() は待機しないので、状態を見る前に描画完了を待つ
    await expect(toggle).toBeVisible();

    // 直前の実行結果に関わらず未フォローから始める
    if ((await toggle.innerText()).includes('フォロー中')) {
        await toggle.click();
        await expect(toggle).not.toContainText('フォロー中');
    }

    await toggle.click();
    await expect(toggle).toContainText('フォロー中');

    await toggle.click();
    await expect(toggle).not.toContainText('フォロー中');

    expect(errors).toEqual([]);
});

test('レシピ詳細から投稿者のプロフィールへ遷移できる', async ({ page }) => {
    const errors = collectPageErrors(page);

    await page.goto('/recipes');
    await recipeCards(page).first().click();

    // 投稿者欄のリンク（名前と @handle を含む）
    const author = page.getByRole('link').filter({ hasText: /@[\w.-]+/ });
    await expect(author).toBeVisible();
    const handle = (await author.innerText()).match(/@([\w.-]+)/)?.[1];

    await author.click();
    await page.waitForURL(`**/users/${handle}`);

    // 投稿者のレシピが並び、件数サマリーが出ていること
    await expect(page.getByText(`@${handle}`)).toBeVisible();
    await expect(page.getByText('フォロワー')).toBeVisible();
    await expect(recipeCards(page).first().locator('img')).toBeVisible();

    expect(errors).toEqual([]);
});

test('ログインするとマイページに自分の情報が出る', async ({ page }) => {
    const errors = collectPageErrors(page);

    await login(page);

    await expect(page.getByRole('heading', { name: 'Test User' })).toBeVisible();
    await expect(page.getByText('フォロー中')).toBeVisible();
    // 自分のプロフィールにフォローボタンは出ない
    await expect(page.getByRole('button', { name: /^フォロー/ })).toHaveCount(0);

    expect(errors).toEqual([]);
});

test('コメントを投稿し、いいねして、削除できる', async ({ page }) => {
    const errors = collectPageErrors(page);
    const body = `E2Eテストのコメント ${Date.now()}`;

    await login(page);
    await page.goto('/recipes/31');

    await page.getByPlaceholder('作ってみた感想を書いてみましょう').fill(body);
    await page.getByRole('button', { name: 'コメントする' }).click();

    const comment = page.locator('li').filter({ hasText: body });
    await expect(comment).toBeVisible();

    // 自分のコメントにいいねして数が増えること
    const good = comment.getByRole('button', { name: 'いいね' });
    await expect(good).toContainText('0');
    await good.click();
    await expect(good).toContainText('1');

    await comment.getByRole('button', { name: 'コメントを削除' }).click();
    await expect(comment).toHaveCount(0);

    expect(errors).toEqual([]);
});

test('お気に入りを登録して一覧に出し、解除できる', async ({ page }) => {
    const errors = collectPageErrors(page);

    await login(page);
    await page.goto('/recipes');

    // シードではタイトルが重複しうるので、リンク先（レシピID）で照合する
    const href = await recipeCards(page).first().getAttribute('href');
    await recipeCards(page).first().click();

    // 「お気に入り」「お気に入り済み」どちらの状態でも同じボタンを掴む
    const toggle = page.getByRole('button', { name: /^お気に入り/ });
    // isVisible() は待機しないので、状態を見る前に描画完了を待つ
    await expect(toggle).toBeVisible();

    // 直前の実行結果に関わらず未登録から始める
    if ((await toggle.innerText()).includes('済み')) {
        await toggle.click();
        await expect(toggle).not.toContainText('済み');
    }

    await toggle.click();
    await expect(toggle).toContainText('済み');

    await page.goto('/favorites');
    await expect(page.getByRole('heading', { name: 'お気に入り' })).toBeVisible();
    await expect(page.locator(`a[href="${href}"]`)).toBeVisible();

    expect(errors).toEqual([]);
});

test('レシピ詳細から献立に追加して献立ページに出し、外せる', async ({ page }) => {
    const errors = collectPageErrors(page);

    await login(page);
    await page.goto('/recipes');

    // シードではタイトルが重複しうるので、リンク先（レシピID）で照合する
    const href = await recipeCards(page).first().getAttribute('href');
    await recipeCards(page).first().click();

    await page.getByRole('button', { name: '献立に追加' }).click();
    const dialog = page.getByRole('dialog');
    const date = await dialog.getByLabel('作る日').inputValue();
    await dialog.getByRole('button', { name: '追加する' }).click();

    // 直前の実行で同じ日に入れたままでも進めるよう、二重登録エラーなら閉じて続ける
    const duplicate = dialog.getByText('その日にはすでに追加されています。');
    // ダイアログは送信中も開いているので、レスポンスの結果（エラーか完了表示）が出るまで待つ
    await expect(duplicate.or(page.getByRole('status'))).toBeVisible();
    if (await duplicate.isVisible()) {
        await dialog.getByRole('button', { name: 'キャンセル' }).click();
    } else {
        await expect(page.getByRole('status')).toContainText('献立に追加しました');
    }

    await page.goto(`/meal-plan?week=${date}`);
    await expect(page.getByRole('heading', { name: '献立', exact: true })).toBeVisible();
    const day = page.locator(`li[data-date="${date}"]`);
    const entry = day.locator('li').filter({ has: page.locator(`a[href="${href}"]`) });
    await expect(entry).toBeVisible();

    await entry.getByRole('button', { name: /を献立から外す$/ }).click();
    await expect(entry).toHaveCount(0);

    expect(errors).toEqual([]);
});

test('献立の週の材料をまとめて買い物リストに追加できる', async ({ page }) => {
    const errors = collectPageErrors(page);

    await login(page);
    await page.goto('/recipes');

    // シードではタイトルが重複しうるので、リンク先（レシピID）で照合する
    const href = await recipeCards(page).first().getAttribute('href');
    await recipeCards(page).first().click();

    // 作る日の初期値は今日。直前の実行で入れたままなら二重登録エラーになるので、閉じて続ける
    await page.getByRole('button', { name: '献立に追加' }).click();
    const dialog = page.getByRole('dialog');
    const date = await dialog.getByLabel('作る日').inputValue();
    await dialog.getByRole('button', { name: '追加する' }).click();
    const duplicate = dialog.getByText('その日にはすでに追加されています。');
    // ダイアログは送信中も開いているので、レスポンスの結果（エラーか完了表示）が出るまで待つ
    await expect(duplicate.or(page.getByRole('status'))).toBeVisible();
    if (await duplicate.isVisible()) {
        await dialog.getByRole('button', { name: 'キャンセル' }).click();
    }

    await page.goto(`/meal-plan?week=${date}`);
    await page.getByRole('button', { name: 'この週の材料を買い物リストに追加' }).click();
    await expect(page.getByRole('status')).toContainText('買い物リストに追加しました');

    // 次の実行で献立の二重登録にならないよう外しておく
    const entry = page.locator(`li[data-date="${date}"] li`).filter({ has: page.locator(`a[href="${href}"]`) });
    await entry.getByRole('button', { name: /を献立から外す$/ }).click();
    await expect(entry).toHaveCount(0);

    await page.goto('/shopping-list');
    await expect(page.getByRole('heading', { name: '買い物リスト' })).toBeVisible();
    await page.getByRole('tab', { name: 'レシピごと' }).click();
    await expect(page.locator(`section a[href="${href}"]`)).toBeVisible();

    expect(errors).toEqual([]);
});

test('ゲストでも今日のおまかせでレシピ詳細へ遷移する', async ({ page }) => {
    const errors = collectPageErrors(page);

    await page.goto('/');
    await page.getByRole('button', { name: '60分以内' }).click();
    await page.getByRole('button', { name: '今日のおまかせ' }).click();

    // 開発DBの中身に依存しないよう、遷移先はレシピ詳細であることだけを確かめる
    await page.waitForURL(/\/recipes\/\d+$/);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    expect(errors).toEqual([]);
});

test('ゲストでもレシピ一覧から冷蔵庫の材料でレシピを探せる', async ({ page }) => {
    const errors = collectPageErrors(page);

    await page.goto('/recipes');
    await page.getByRole('link', { name: '冷蔵庫の材料から探す' }).click();
    await page.waitForURL(/\/recipes\/by-ingredients$/);
    await expect(page.getByText('材料を入れてください')).toBeVisible();

    const input = page.getByLabel('材料を追加');
    await input.fill('玉ねぎ');
    await input.press('Enter');

    await expect(page.getByRole('button', { name: '玉ねぎを外す' })).toBeVisible();
    await page.waitForURL(/ingredients/);
    // 開発DBの中身に依存しないよう、結果か「見つかりません」のどちらかが出ることだけを確かめる
    await expect(page.getByText(/\d+件|その材料を使うレシピが見つかりません/).first()).toBeVisible();

    // 5つ入れると入力できなくなる
    for (const name of ['にんじん', '卵', '豆腐', '塩']) {
        await input.fill(name);
        await input.press('Enter');
        await expect(page.getByRole('button', { name: `${name}を外す` })).toBeVisible();
    }
    await expect(input).toBeDisabled();

    await page.getByRole('button', { name: '玉ねぎを外す' }).click();
    await expect(input).toBeEnabled();

    expect(errors).toEqual([]);
});

test('ゲストでもレシピ詳細から調理モードで手順を送り、閉じると詳細に戻る', async ({ page }) => {
    const errors = collectPageErrors(page);

    await page.goto('/recipes');
    await recipeCards(page).first().click();
    await page.waitForURL(/\/recipes\/\d+$/);

    // 開発DBの中身に依存しないよう、手順数は詳細ページの表示から数える
    const stepCount = await page.locator('section:has(h2:text-is("作り方")) ol > li').count();

    await page.getByRole('button', { name: '調理モード' }).click();
    const dialog = page.getByRole('dialog');
    await expect(dialog.getByText(`手順 1 / ${stepCount}`)).toBeVisible();
    await expect(dialog.getByRole('button', { name: '前へ' })).toBeDisabled();

    if (stepCount >= 2) {
        await dialog.getByRole('button', { name: '次へ' }).click();
        await expect(dialog.getByText(`手順 2 / ${stepCount}`)).toBeVisible();
        await dialog.getByRole('button', { name: '前へ' }).click();
        await expect(dialog.getByText(`手順 1 / ${stepCount}`)).toBeVisible();
    }

    await dialog.getByRole('button', { name: 'Close' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.getByRole('heading', { name: '作り方' })).toBeVisible();

    expect(errors).toEqual([]);
});

test('レシピ詳細で人数を変えると材料の分量が換算される', async ({ page }) => {
    const errors = collectPageErrors(page);
    // 開発DBに対して実行するので、分量が決まったレシピを下書きで作り、最後に消す
    const title = `人数換算e2e ${Date.now()}`;

    await login(page);
    await page.goto('/recipes/create');
    await page.getByLabel('タイトル').fill(title);
    await page.getByLabel('何人分').fill('2');

    const quantities = ['大さじ1', '1/2個', '少々'];
    for (const [index, quantity] of quantities.entries()) {
        if (index > 0) {
            await page.getByRole('button', { name: '材料を追加' }).click();
        }
        await page.getByLabel(`材料${index + 1}の名前`).fill(`材料${index + 1}`);
        await page.getByLabel(`材料${index + 1}の分量`).fill(quantity);
    }

    await page.getByRole('button', { name: '下書き保存' }).click();
    // 下書き保存後は編集画面に戻る
    await page.waitForURL(/\/recipes\/\d+\/edit$/);
    const recipeId = page.url().match(/\/recipes\/(\d+)\/edit$/)?.[1];
    await page.goto(`/recipes/${recipeId}`);

    const ingredients = page.locator('section:has(h2:has-text("材料")) ul > li');
    await expect(page.getByRole('heading', { name: '材料（2人分）' })).toBeVisible();
    await expect(ingredients.nth(0)).toContainText('大さじ1');

    await page.getByRole('button', { name: '人数を増やす' }).click();
    await page.getByRole('button', { name: '人数を増やす' }).click();

    await expect(page.getByRole('heading', { name: '材料（4人分）' })).toBeVisible();
    await expect(ingredients.nth(0)).toContainText('大さじ2');
    await expect(ingredients.nth(1)).toContainText('1個');
    await expect(ingredients.nth(2)).toContainText('少々');

    await page.getByRole('button', { name: '元に戻す' }).click();
    await expect(page.getByRole('heading', { name: '材料（2人分）' })).toBeVisible();
    await expect(ingredients.nth(1)).toContainText('1/2個');

    await page.goto('/drafts');
    await page.getByRole('listitem').filter({ hasText: title }).getByRole('button', { name: '削除' }).click();
    await page.getByRole('dialog').getByRole('button', { name: '削除する' }).click();
    await expect(page.getByRole('dialog')).toBeHidden();
    await expect(page.getByRole('link', { name: title })).toBeHidden();

    expect(errors).toEqual([]);
});

test('買い物リストで別レシピの同じ材料が1行にまとまり、チェック・削除が全行に効く', async ({ page }) => {
    const errors = collectPageErrors(page);

    await login(page);

    // 1つ目のレシピと、その最初の材料名
    await page.goto('/recipes');
    const firstHref = (await recipeCards(page).first().getAttribute('href'))!;
    await page.goto(firstHref);
    const name = (await ingredientNames(page).first().textContent())!.trim();

    // 同じ名前の材料を持つ別レシピを探す。検索は部分一致なので、詳細で完全一致を確かめる
    await page.goto(`/recipes/by-ingredients?ingredients[0]=${encodeURIComponent(name)}`);
    await expect(page.getByText(/\d+件/).first()).toBeVisible();
    const candidates = (await recipeCards(page).evaluateAll((links) => links.map((link) => link.getAttribute('href') ?? ''))).filter(
        (href) => href !== firstHref,
    );
    let secondHref: string | undefined;
    for (const href of candidates) {
        await page.goto(href);
        if ((await ingredientNames(page).allTextContents()).some((text) => text.trim() === name)) {
            secondHref = href;
            break;
        }
    }
    expect(secondHref, `「${name}」を使う別レシピ`).toBeDefined();
    const recipeHrefs = [firstHref, secondHref!];

    // 前回の残りを片付けてから、2つのレシピを買い物リストに入れる
    for (const href of recipeHrefs) {
        await removeRecipeFromCart(page, href);
    }
    for (const href of recipeHrefs) {
        await page.goto(href);
        await page.getByRole('button', { name: '買い物リストに追加', exact: true }).click();
        await expect(page.getByRole('button', { name: '買い物リストに追加済み' })).toBeVisible();
    }

    // 材料ごと（初期表示）では1行にまとまり、分量が並ぶ。片付けで保存された切替状態を消して初期表示を見る
    await page.evaluate(() => localStorage.removeItem('shopping-list-view'));
    await page.goto('/shopping-list');
    await expect(page.getByRole('tab', { name: '材料ごと' })).toHaveAttribute('aria-selected', 'true');
    const mergedRow = page.locator('li').filter({ has: page.getByRole('button', { name: `${name}を削除`, exact: true }) });
    await expect(mergedRow).toHaveCount(1);
    await expect(mergedRow).toContainText(' + ');

    // まとめた行のチェックが、レシピごと表示の両方のレシピの行に効く。
    // 他のテストが入れたレシピにも同じ材料がありうるので、ページ全体ではなく追加した2レシピのセクションで確かめる
    await mergedRow.locator('button[aria-pressed="false"]').click();
    await expect(mergedRow.locator('button[aria-pressed="true"]')).toBeVisible();
    await page.getByRole('tab', { name: 'レシピごと' }).click();
    const rowsIn = (href: string) =>
        page
            .locator('section')
            .filter({ has: page.locator(`a[href="${href}"]`) })
            .locator('li')
            .filter({ has: page.getByRole('button', { name: `${name}を削除`, exact: true }) });
    for (const href of recipeHrefs) {
        await expect(rowsIn(href).first()).toBeVisible();
        await expect(rowsIn(href).locator('button[aria-pressed="false"]')).toHaveCount(0);
    }

    // 切替は再読み込みしても保たれる
    await page.reload();
    await expect(page.getByRole('tab', { name: 'レシピごと' })).toHaveAttribute('aria-selected', 'true');

    // まとめた行の削除で、両方の行が消える
    await page.getByRole('tab', { name: '材料ごと' }).click();
    await mergedRow.getByRole('button', { name: `${name}を削除`, exact: true }).click();
    await expect(mergedRow).toHaveCount(0);
    await page.getByRole('tab', { name: 'レシピごと' }).click();
    for (const href of recipeHrefs) {
        await expect(rowsIn(href)).toHaveCount(0);
    }

    // 次の実行や他のテストに残さない
    for (const href of recipeHrefs) {
        await removeRecipeFromCart(page, href);
    }

    expect(errors).toEqual([]);
});
