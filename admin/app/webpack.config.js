const path = require('path');

module.exports = (env, argv) => {
    let production = argv.mode === 'production'

    return {
        entry: {
            'js/admin': path.resolve(__dirname, 'src/admin.js')
        },

        output: {
            filename: '[name].js',
            path: path.resolve(__dirname, 'assets'),
        },

        devtool: production ? '' : 'source-map',

        resolve: {
            extensions: [".js", ".jsx"], //.json?
        },

        module: {
            rules: [
                {
                    test: /\.jsx?$/,
                    exclude: /node_modules/,
                    loader: 'babel-loader',
                },
                {
                    test: /\.css$/i,
                    use: [
                        "style-loader",
                        { loader: "css-loader", options: { importLoaders: 1 } },
                        //"postcss-loader"
                    ]
                }
            ],
        },
        node: {
            fs: 'empty'
        }
    };
}