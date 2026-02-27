import axios from "axios";
import config from "./config";

axios.defaults.headers.post['Content-Type'] = 'application/json+ld';
axios.defaults.baseURL = config.get('apiUrl');

class APICore {
    /**
     * Fetches data from given url
     */
    get = (url: string, params?: { [key: string]: any }) => {
        let response;
        if (params) {
            const queryStringArray = params
                ? Object.keys(params)
                    .map((key) => {
                        if ('object' === typeof params[key]) {
                            return params[key].map((value: any) => key + "[]=" + value)
                        }

                        return key + "=" + params[key];
                    })
                : [];

            const queryString = queryStringArray.flat().join('&');

            response = axios.get(`${url}?${queryString}`, params);
        } else {
            response = axios.get(`${url}`, params);
        }
        return response;
    };

    getFile = (url: string, params: { [key: string]: any }) => {
        let response;
        if (params) {
            const queryString = params
                ? Object.keys(params)
                    .map((key) => key + '=' + params[key])
                    .join('&')
                : '';
            response = axios.get(`${url}?${queryString}`, {responseType: 'blob'});
        } else {
            response = axios.get(`${url}`, {responseType: 'blob'});
        }
        return response;
    };

    getMultiple = (urls: string[], params: { [key: string]: any }) => {
        const reqs = [];
        let queryString = '';
        if (params) {
            queryString = params
                ? Object.keys(params)
                    .map((key) => key + '=' + params[key])
                    .join('&')
                : '';
        }

        for (const url of urls) {
            reqs.push(axios.get(`${url}?${queryString}`));
        }
        return axios.all(reqs);
    };

    /**
     * post given data to url
     */
    create = (url: string, data: object, options?: object) => {
        return axios.post(url, data, options);
    };

    /**
     * Updates patch data
     */
    updatePatch = (url: string, data: object) => {
        return axios.patch(url, data, {
            headers: {
                'Content-Type': 'application/merge-patch+json',
            }
        });
    };

    /**
     * Updates data
     */
    update = (url: string, data: object) => {
        return axios.put(url, data);
    };

    /**
     * Deletes data
     */
    delete = (url: string) => {
        return axios.delete(url);
    };
}

export default APICore;