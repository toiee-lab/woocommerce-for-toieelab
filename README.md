# WooCommerce Extension for toiee Lab

**ver3.0で、大幅な仕様変更を行いました。アップデートの際は「必ず」、初期設定を実行してください**


WordPress の No.1 ショッピングカート WooCommerce の購入情報を参照して「コンテンツの表示、非表示」を制御するプラグインです。 

以下のプロダクトタイプを識別することができます。

- プロダクト（通常の商品）
- バリエーションプロダクト
- メンバーシップ（WooCommerce Membership）
- サブスクリプション (WooCommerce Subscription)

また、複数の商品をまとめて「許可」する仕組みも用意しています。複数ページに渡って許可を与えたり、様々なグレードやプランが関連する場合に、便利に使えます。

ver0.3 からは、Seriously Simple Podcast の閲覧制限機能も統合しました。
ver0.4 からは、マイライブラリ機能や、Advanced Custom Fieldsを統合して、使いやすくしました。

## 下位互換について

- Seriously Simple Podcast の Feed Detail の設定を優先します
- なるべく、新しい設定にしてください
- その際、Feed Detail の設定を削除（制限しないをチェックも）してください
- その上で、新しい方を使ってください


## 必要なもの

- WordPress
- WooCommerce

[詳しくは、Wiki をご覧ください](https://github.com/toiee-lab/woocommerce-for-toieelab/wiki)


## 初期設定

- ACF をインストールする
- 管理画面 > 設定 > WC for toiee > 機能の選択 **必ず、行ってください**
- もし、ページが表示されないなど、パーマリンクの問題があれば「パーマリンクの再設定」を行ってください

## 履歴

- v0.4.3 : slug が翻訳ファイルによって変更されてしまうので、podcast を `ssp_archive_slug` フィルターで指定（rewrite rule を flash しないといけない）



