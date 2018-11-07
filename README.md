# WooCommerce Extension for toiee Lab

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

## インストール

このプラグインのソースをダウンロードして、 `プラグイン > 新規作成 > インストール > 有効化` します。

次に、 `設定 > WC Restrct` を開いて、「閲覧制限メッセージ」を設定します。ここで設定したメッセージが、「制限されて閲覧できないユーザーに表示」されます。

### 変数について

閲覧制限メッセージを作成する場合、「変数」が利用できます。変数を利用することで、柔軟な表示が可能です。

- `{{product_url}}` は、商品ページのurlに置き換わります(idで先頭に指定されているものを利用)
- `{{product_name}}` は、商品の名前に置き換わります(idで先頭に指定されている商品の名前)
- `{{login_url}}` は、アカウントページへのリンクを表示します
- `{{modal_id}}` は、 modal-(product.ID) に置き換わります
- `{{display_none}}` は、ログイン済みなら style="display:none;" が挿入されます
- `{{login_form}}` は、このページにリダイレクトされるログインフォームを表示します( woocommerce_login_form() を利用 )
- `{{message}}` は、ショートコードで指定したメッセージに置き換えます


### KANSOテーマの場合

KANSOテーマは、uikit を利用しているため、以下のようなソースコードを利用すると「ポップアップのログイン画面」が現れるようになるので、オススメです。

```
<div class="uk-alert-none uk-alert">
    <h3><span class="uk-icon"></span> お知らせ </h3>
    <p>この記事は、{{product_name}} 会員限定です。</p>
    <p class="uk-text-center">
        <a href="{{product_url}}" class="uk-button uk-button-primary " title="お申し込み">お申し込み</a> <button class="uk-button uk-button-default" type="button" uk-toggle="target: #{{modal_id}}" {{display_none}}>会員ログイン</button>
</p>
<p>{{message}}</p>
</div>

<!-- This is the modal -->
<div id="{{modal_id}}" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <h3 class="uk-modal-title">会員ログイン</h3>
        {{login_form}}
        <p class="uk-text-right">
            <button class="uk-button uk-button-default uk-modal-close" type="button">閉じる</button>
        </p>
    </div>
</div>
```


### 通常のテーマの場合

```
<div class="uk-alert-none uk-alert">
    <h3><span class="uk-icon"></span> お知らせ </h3>
    <p>この記事は、{{product_name}} 会員限定です。</p>
    <p class="uk-text-center">
        <a href="{{product_url}}" class="uk-button uk-button-primary " title="">お申し込み</a> <a href="{{login_url}}" class="uk-button uk-button-default " title="">会員ログイン</a>
</p>

<p>{{message}}</p>

</div>
```


---


## 使い方

閲覧制限を行うには、以下のショートコードを行います。
offer で、商品IDを指定すると、その商品へのボタンを表示します。デフォルトでは、idsの中からはじめに見つかった商品へのリンクを出します。
XXX,YYY は、商品のIDと商品まとめ投稿タイプのIDを混ぜて使えます。

```
[wcr-content ids="XXX,YYY" offer="ZZZ" message="資料をご覧になるには、お申し込みが必要です"]

ここに閲覧制限コンテンツ

[/wcr-content]
```



### 2つの方法

- 複数の条件をまとめて「商品まとめ 投稿タイプ」で設定する
- 個々のページで、商品を指定する

オススメは、 **複数の条件をまとめる、商品まとめ** を使うことです。後から、商品を追加したりすることが可能で、変更しやすくなります。


### 基本的な使い方

まず、以下のようなショートコードを制限したいコンテンツに記載します。
p1, p2, p3 のように、コンマ区切りで商品IDを指定することで、**いずれかの商品を購入して入れば、アクセス可能** とすることができます。

```
[wcr-content ids="p1,p2,p3"]
ここにコンテンツ
[/wcr-content]
```

また、「ログインフォームを表示しない」形で、コンテンツを表示、非表示することができます。例えば、以下のように記述することができます。

```
[wcr-content ids="..." show_to_not_grantee_mode="true"]
このコンテンツは、アクセスできない人にだけ表示されます。アクセスできる人には、何も表示されません。
[/wcr-content]
```

利用できるパラメータは、以下の通りです。

- `show_to_not_grantee_mode` : アクセスできない人にだけコンテンツを表示します。アクセスできる人には、何も表示しません。
- `show_to_grantee_mode` : アクセスできる人にだけコンテンツを表示します。アクセスできない人には、何も表示しません。


### 商品まとめ投稿タイプ について

![商品まとめ設定](https://user-images.githubusercontent.com/7563975/39902882-4ab30b52-550b-11e8-85c6-d9b8eb728ffc.png)

プロダクトID、subscription、Membership を設定できます。設定したものの **いずれか** がマッチすれば、許可をします。





### バリエーションについて

このプラグインは「バリエーション」にも対応しています。たとえば、「有料サポート」に、Standard、Gold、Premium を用意したいとします。この場合、WooCommerceで「商品:サポート」を追加したのち、「バリエーションプロダクト」として、複数のグレードを設定します。

各バリエーションにはIDが付与されるので、そのIDをショートコードに記載することで「グレード別にアクセスできる記事を制御」することが可能です。

なお、 Standard < Gold < Premium などで上位のプランが下位のプランのコンテンツにもアクセスさせたい場合は、一つ下のIDも含めるようにしてください。

以下のようになります。

```
// Standard Plan id: 123, Gold Plan id: 243, Premium Plan id: 321 とした場合

[wcr-content ids="123,243,321"]
ここはスタンダードプラン以上が閲覧できます。
[/wcr-content]

[wcr-content ids="243,321"]
ここはゴールドプラン以上が閲覧できます。
[/wcr-content]

[wcr-content ids="321"]
ここはプレミアムプラン以上が閲覧できます。
[/wcr-content]
```

## メンバーシップについて


なお、WooCommerce Membership は、[Membership専用のショートコード](https://docs.woocommerce.com/document/woocommerce-memberships-restrict-content/#section-6)を持っています。シンプルに「メンバーに対してだけ見せたい」場合は、こちらのショートコードを使えば良いでしょう。







