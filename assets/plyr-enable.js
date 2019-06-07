/**
 * Plyr.io のプレイヤーを生成するためのもの
 *
 */


/**
 * 自動で「次のプレイヤーを再生」する設定を行う
 * @param players
 */
let set_autoplay = function ( players ) {
    players.map(
        function (player, index, players) {
            if (index >= players.length - 1) return;

            players[index].on(
                'ended',
                function () {
                    let i = players.lastIndexOf(player, players.length - 1);
                    if (-1 < i) {
                        players[i + 1].speed = player.speed;
                        players[i + 1].play();
                    }
                }
            );
        }
    );
};

/* アーカイブページの場合を想定した動作 = 次を自動再生する複数プレイヤーを生成 */
let list    = ['scrum_episode', 'tlm_input--', 'tlm_archive'];

for( let i=0, len=list.length; i<len; i++ ) {
    let name    = list[ i ];
    let players = Plyr.setup( '.plyr-player-' + name );
    if ( null !== players ) {
        set_autoplay( players );
    }
}

/* 個別ページなどで、単独でプレイヤーが設置されている場合を想定 */
let plyr = Plyr.setup( '.plyr-player' );
