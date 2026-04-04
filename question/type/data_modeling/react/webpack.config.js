var path = require('path');
var webpack = require('webpack');

module.exports = (env, options) => {
    exports = {
        entry: './src/index.tsx',
        output: {
            path: path.resolve(__dirname, '../amd/build'),
            publicPath: '/dist/',
            filename: 'app-lazy.min.js',
            chunkFilename: "[id].app-lazy.min.js?v=[hash]",
            libraryTarget: 'amd',
        },
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: ['babel-loader'],
                },
                {
                    test: /\.tsx?$/,
                    use: 'ts-loader',
                    exclude: /node_modules/,
                },
                {
                    test: /\.css$/,
                    use: ['style-loader', 'css-loader'],
                },
                {
                    test: /\.svg$/,
                    loader: 'svg-inline-loader',
                },
                {
                    test: /\.(png|jpe?g|gif)$/i,
                    use: [
                        {
                            loader: 'file-loader',
                        },
                    ],
                },
                {
                    test: /\.s[ac]ss$/i,
                    use: ['style-loader', 'css-loader', 'sass-loader'],
                },
                {
                    test: /\.(woff|woff2|ttf|eot)$/,
                    use: 'file-loader?name=fonts/[name].[ext]!static',
                },
            ],
        },
        resolve: {
            extensions: ['.tsx', '.ts', '.js'],
            alias: {
                "@colors": path.resolve(`${__dirname}/src`, "colors"),
                "@components": path.resolve(`${__dirname}/src`, "components"),
                "@constants": path.resolve(`${__dirname}/src`, "constants"),
                "@pages": path.resolve(`${__dirname}/src`, "pages"),
                "@store": path.resolve(`${__dirname}/src`, "store"),
                "@projectTypes": path.resolve(`${__dirname}/src`, "types"),
                "@scss": path.resolve(`${__dirname}/../`, "scss"),
                "@widgets": path.resolve(`${__dirname}/src`, "widgets"),
            }
        },
        watchOptions: {
            ignored: /node_modules/
        },
        externals: {
            'core/ajax': {
                amd: 'core/ajax'
            },
            'core/str': {
                amd: 'core/str'
            },
            'core/modal_factory': {
                amd: 'core/modal_factory'
            },
            'core/modal_events': {
                amd: 'core/modal_events'
            },
            'core/fragment': {
                amd: 'core/fragment'
            },
            'core/yui': {
                amd: 'core/yui'
            },
            'core/localstorage': {
                amd: 'core/localstorage'
            },
            'core/notification': {
                amd: 'core/notification'
            },
            'core/templates': {
                amd: 'core/templates'
            },
            'jquery': {
                amd: 'jquery'
            }
        }
    };

    return exports;
};

