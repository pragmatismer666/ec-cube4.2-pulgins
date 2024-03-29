# komoju-eccube-4-v1

Copyright Subspire Inc. 2020

[EC-CUBE](http://www.ec-cube.net)用の[KOMOJU](https://komoju.com/)の決済プラグインです。

このプラグインはEC-CUBE上での買い物をKOMOJUで決済する機能を提供します。
決済のみを行うシンプルなプラグインで、受注や集計はEC-CUBEで管理する方針です。
購入者のクレジットカード情報はデータベースに保存せず、ショップのオーナーも見ることはできません。

「認証と売上処理」設定の場合はチェックアウト時にオーソリと売上処理が行われて、EC-CUBEに入ってくる注文は自動的に【入金済み】ステータスに更新されます。また、ポイント反映もこの時点で自動的に行われます。

「認証のみ」設定の場合はチェックアウト時に認証のみ行われて、EC-CUBE管理画面上から支払い請求・払い戻しができます。支払い請求時は注文が自動的に【入金済み】ステータスに更新されます。また、ポイント反映もこの時点で自動的に行われます。払い戻し時は注文が自動的に【注文取消し】ステータスに更新されます。

本プラグインで利用可能な決済方法はVisa, Mastercard, JCB, AMEX, Diners, Discover, コンビニ払い、銀行振込、ペイジー、デジタルマネーです。

## 機能

- [KOMOJU](https://komoju.com/)にて簡単な登録を行うことで試せます。
- [MultiPay](https://docs.komoju.com/en/multipay/overview/)を利用して安全に購入処理をします。
- カード情報は KOMOJU のサーバに安全に格納されます。
- カード情報非通過対応済み
- 認証のみ・認証と売上処理の決済方法が可能
- EC-CUBE管理画面で支払い請求・払い戻しができる
- EC-CUBE管理画面で返金及び一部返金ができる
- EC-CUBE管理画面で各注文の KOMOJU 取引詳細リンクを表示

## 対応環境

- EC-CUBE 4.0.0 以上

## インストール方法
- swiftmailer の公式ライブラリをインストールします。EC-CUBEフォルダで `composer require` を実行してください。

```
composer require "swiftmailer/swiftmailer": "^6.1"
composer require "symfony/swiftmailer-bundle": "^3.1"
```

1) プラグインを管理画面からインストールします。

```
1-1) EC-CUBE管理画面の【オーナーズストア】→【プラグイン】→【プラグイン一覧】から
【アップロードして新規追加】のボタンをクリックしていただいて、その後本プラグインのZIPファイルを
選択していただいて、アップロードしていただけます。

1-2) EC-CUBE管理画面の【オーナーズストア】→【プラグイン】→【プラグイン一覧】から
【KOMOJU決済プラグイン】の【有効にする】をクリックしてください。

1-3) EC-CUBE管理画面の【コンテンツ管理】→【キャッシュ管理】で【キャッシュ削除】を押していただきキャッシュをクリアしてください。

1-4) EC-CUBE管理画面の【KOMOJUマルチ決済】→【設定】にてライセンスキーとご購入時にご利用いただいたメールアドレスをご入力ください。
ライセンス認証は1回だけ行えます。先にテスト用でご利用いただく場合は【テスト環境】ボタンを押してください。
テストが完了してテストと本番が同じ環境でしたら一度プラグインをアンインストールしていただき、再度インストールしていただきライセンス認証を行ってください。
また、追加分のライセンスキーが必要でしたら弊社までご連絡ください。1ライセンスにつき一つのEC-CUBE環境でご利用いただけます。

1-5) EC-CUBE管理画面の【KOMOJUマルチ決済】→【設定】にてご自身のAPIキーを登録してください。
KOMOJUにてメールアドレスを登録するだけでAPIキーを取得できます。
非公開鍵、公開用鍵、クライアント UUIDはKOMOJU管理画面の設定ページから取得してください。
そして、KOMOJU管理画面のWebhookページでWebhookを追加していただき、「シークレットトークン」と「WebhookエンドポイントURL」はEC-CUBE管理画面の【KOMOJUマルチ決済】→【設定】のあるものをご利用ください。
また、認証のみ・認証と売上処理の決済方法も選択してください。

1-6) EC-CUBE管理画面の【設定】→【店舗設定】→【配送方法設定】から対象になる配送方法で【マルチ決済】のチェックを入れて保存してください。

その後、チェックアウトでKOMOJUが利用可能になります。
```

## アップデート方法

旧バージョンからのアップデート方法が下記になります。アップデート時は必ず下記の手順でアップデートをお願いいたします。

1) プラグインを管理画面から更新します。

```
1-1) EC-CUBE管理画面の【オーナーズストア】→【プラグイン】→【プラグイン一覧】からプラグインを無効にします。

1-2) EC-CUBE管理画面の【オーナーズストア】→【プラグイン】→【プラグイン一覧】からプラグインを更新します。

1-3) EC-CUBE管理画面の【オーナーズストア】→【プラグイン】→【プラグイン一覧】からプラグインを有効にします。

1-4) EC-CUBE管理画面の【コンテンツ管理】→【キャッシュ管理】で【キャッシュ削除】を押していただきキャッシュをクリアしてください。
```

## 利用方法

- EC-CUBE管理画面の【設定】→【店舗設定】→【配送方法設定】から対象になる配送方法で【マルチ決済】のチェックを入れて保存します。

- 商品を購入する際に、支払方法に「マルチ決済」を選択すると、注文処理する時にポップアップでカード情報を入力するフォームが表示されます。

- テスト環境では[テストカード](https://docs.komoju.com/en/api/overview/#payment_details)を利用して試すことが可能です。