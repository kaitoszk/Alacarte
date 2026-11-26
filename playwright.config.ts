import { defineConfig, devices } from '@playwright/test';

// CI では php artisan serve + SQLite で立てたアプリを見る。ローカルは Docker の app コンテナを見る
const isCI = !!process.env.CI;
const ciBaseURL = 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: './e2e',
    // 全テストが同じ開発DBと同じテストユーザーを触るため、並列にすると
    // お気に入り/フォローのトグルが互いの状態を壊す。直列で走らせる
    fullyParallel: false,
    workers: 1,
    // CI では一度だけ再試行する（再試行で通ったものは flaky として出力に残る）
    retries: isCI ? 1 : 0,
    reporter: [['list']],
    use: {
        baseURL: isCI ? ciBaseURL : 'http://localhost:8080',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    webServer: isCI
        ? {
              command: 'php artisan serve --host=127.0.0.1 --port=8000',
              url: ciBaseURL,
              reuseExistingServer: false,
              timeout: 60_000,
              // サーバーが落ちたときに理由（Fatal error など）を CI ログで追えるよう、出力をそのまま流す
              stdout: 'pipe',
              stderr: 'pipe',
          }
        : undefined,
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
