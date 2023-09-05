# eccube-restrict-email-domain-v1

Copyright Subspire Inc. 2021

注文完了メールを受け取り出来ない@ezweb.ne.jp 、@docomo.ne.jp、 @softbank.ne.jp、@i.softbank.jpなど携帯キャリアメールアドレスやフリーメールアドレスを会員登録・ゲストチェックアウト・管理画面会員編集で利用不可とするためのプラグインです。

こちらのプラグインで利用不可にしたいメールアドレスのドメインを自由に管理画面から設定することができます。

## 機能

- 指定したドメイン名を会員登録・ゲストチェックアウト・管理画面会員編集で利用不可とする機能
- エラーテキストは簡単に言語ファイルから変更できます（Resource/locale/messages.ja.yml）

## 対応環境

- PHP 7.0 以上
- EC-CUBE 4.0.0 以上

## インストール方法

1) プラグインを管理画面からインストールします。

```
1-1)EC-CUBE管理画面の【オーナーズストア】→【プラグイン】→【プラグイン一覧】から
【アップロードして新規追加】のボタンをクリックしていただいて、その後本プラグインのZIPファイルを
選択していただいて、アップロードしていただけます。

1-2)EC-CUBE管理画面の【オーナーズストア】→【プラグイン】→【プラグイン一覧】から
【メールアドレスドメイン制限】の【有効にする】をクリックしてください。

1-3)EC-CUBE管理画面の【オーナーズストア】→【プラグイン】→【プラグイン一覧】から
【メールアドレスドメイン制限】の【設定】リンクをクリックして、本プラグインの設定を行ってください。

その後はプラグイン設定ページで設定を変更していただき保存していただければご利用いただけます。
```

## 利用方法

- 管理画面のプラグイン設定ページから利用不可にしたいドメイン名を入力して登録していただければ反映されます。