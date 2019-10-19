

const SimpleStore = () => {

    const store = {};

    const set = (key,value) => {
        let newPair = {}
        newPair[key] = value
        Object.assign(store, newPair)
    }

    const get = (key,_default) => {
        if(typeof store[key] !== "undefined") return store[key];
        if(typeof _default !== "undefined") return _default;
        return null;
    }

    return {
        set,
        get
    }
}

const simpleStore = SimpleStore();
export default simpleStore;