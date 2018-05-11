# WooCommerce Simple Restrict Content Plugin

WordPress の No.1 ショッピングカート WooCommerce の購入情報を参照して「コンテンツの表示、非表示」を制御するプラグインです。

以下のプロダクトタイプを識別することができます。

- プロダクト（通常の商品）
- バリエーションプロダクト
- メンバーシップ（WooCommerce Membership）
- サブスクリプション (WooCommerce Subscription)

また、複数の商品をまとめて「許可」する仕組みも用意しています。複数ページに渡って許可を与えたり、様々なグレードやプランが関連する場合に、便利に使えます。


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

```
[wc-restrict id="XXX,YYY" wcr_id="ZZZ" message="資料をご覧になるには、お申し込みが必要です"]

ここに閲覧制限コンテンツ

[/wc-restrict]
```



### 2つの方法

- 複数の条件をまとめて「WC-Restrict 投稿タイプ」で設定する
- 個々のページで、商品を指定する

オススメは、 **複数の条件をまとめる、WC-Restrict** を使うことです。後から、商品を追加したりすることが可能で、変更しやすくなります。


### 基本的な使い方

まず、以下のようなショートコードを制限したいコンテンツに記載します。
p1, p2, p3 のように、コンマ区切りで商品IDを指定することで、**いずれかの商品を購入して入れば、アクセス可能** とすることができます。

```
[wc-restrict id="p1,p2,p3"]
ここにコンテンツ
[/wc-restrict]
```


利用できるパラメータは、

- `wcr_id` : WC-Restrict投稿タイプで設定した条件を指定できます
- `id` : プロダクト、プロダクト・バリエーションを指定できます
- `mem_id` : メンバーシップを指定できます
- `sub_id` : サブスクリプションを指定できます

また、「ログインフォームを表示しない」形で、コンテンツを表示、非表示することができます。例えば、以下のように記述することができます。

```
[wc-restrict wcr_id="..." show_to_not_grantee_mode="true"]
このコンテンツは、アクセスできない人にだけ表示されます。アクセスできる人には、何も表示されません。
[/wc-restrict]
```

利用できるパラメータは、以下の通りです。

- `show_to_not_grantee_mode` : アクセスできない人にだけコンテンツを表示します。アクセスできる人には、何も表示しません。
- `show_to_grantee_mode` : アクセスできる人にだけコンテンツを表示します。アクセスできない人には、何も表示しません。


### WC-Restrict について

![WC-Restrict設定](https://user-images.githubusercontent.com/7563975/39902882-4ab30b52-550b-11e8-85c6-d9b8eb728ffc.png)

プロダクトID、subscription、Membership を設定できます。設定したものの **いずれか** がマッチすれば、許可をします。





### バリエーションについて

このプラグインは「バリエーション」にも対応しています。たとえば、「有料サポート」に、Standard、Gold、Premium を用意したいとします。この場合、WooCommerceで「商品:サポート」を追加したのち、「バリエーションプロダクト」として、複数のグレードを設定します。

各バリエーションにはIDが付与されるので、そのIDをショートコードに記載することで「グレード別にアクセスできる記事を制御」することが可能です。

なお、 Standard < Gold < Premium などで上位のプランが下位のプランのコンテンツにもアクセスさせたい場合は、一つ下のIDも含めるようにしてください。

以下のようになります。

```
// Standard Plan id: 123, Gold Plan id: 243, Premium Plan id: 321 とした場合

[wc-restrict id="123,243,321"]
ここはスタンダードプラン以上が閲覧できます。
[/wc-restrict]

[wc-restrict id="243,321"]
ここはゴールドプラン以上が閲覧できます。
[/wc-restrict]

[wc-restrict id="321"]
ここはプレミアムプラン以上が閲覧できます。
[/wc-restrict]
```

### Membership について

メンバーシップについても同様にアクセス制限が行えます。xxx はプロダクト IDです。

```
[wc-restrict mem_id="xxx"]
ここにコンテンツ
[/wc-restrict]
```


メンバーシップと、商品を同時に指定することも可能です。

```
[wc-restrict id="p1" mem_id="m2"]
ここにコンテンツ。
商品ID:p1を持つ人と、メンバーシップID:m2を持つ人が閲覧できます。
[/wc-restrict]
```

> なお、WooCommerce Membership は、[Membership専用のショートコード](https://docs.woocommerce.com/document/woocommerce-memberships-restrict-content/#section-6)を持っています。シンプルに「メンバーに対してだけ見せたい」場合は、こちらのショートコードを使えば良いでしょう。


### Subsrciption で制限する

WooCommerce Subscription 商品を使って「閲覧制限」を行うことができます。

```
[wc-restrict sub_id="xxx"]
ここは xxx IDのサブスクリプション商品の人だけが閲覧できるコンテンツ
[/wc-restrict]
```

なお、 id, mem_id オプションを同時に指定することができます。





